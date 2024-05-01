<?php

namespace CoursewareGPTBlock;

use Courseware\BlockTypes\BlockType;
use Opis\JsonSchema\Schema;

class GPTBlock extends BlockType
{

    public static function getType(): string
    {
        return 'gpt';
    }

    public static function getTitle(): string
    {
        return dgettext('CoursewareGPTBlock', 'StudiGPT (experimentell)');
    }

    public static function getDescription(): string
    {
        return dgettext('CoursewareGPTBlock', 'Generiert Fragen zu den Inhalten einer Courseware-Seite und gibt Feedback zu einer Antwort mithilfe von GPT.');
    }

    public function initialPayload(): array
    {
        return [
            'title' => '',
            'api_key_origin' => 'global',
            'customChatModel' => '',
            'summary' => '',
            'additional_instructions' => '',
            'language' => 'de_DE',
            'difficulty' => 'easy',
            'questionMode' => 'pool',
            'questionCount' => 5,
            'use_block_contents' => false,
        ];
    }

    public static function getJsonSchema(): Schema
    {
        $schema = file_get_contents(__DIR__ . '/GPTBlock.json');

        return Schema::fromJsonString($schema);
    }

    public static function getCategories(): array
    {
        return ['interaction'];
    }

    public static function getContentTypes(): array
    {
        return ['text'];
    }

    public static function getFileTypes(): array
    {
        return [];
    }

    public function getPayload()
    {
        $payload = parent::getPayload();
        $user = \User::findCurrent();

        $structural_element = $this->block->container->getStructuralElement();
        $can_edit = $structural_element->canEdit($user);

        $payload['has_global_api_key'] = GPTClient::hasGlobalApiKey();
        $payload['has_custom_api_key'] = GPTClient::hasCustomApiKey($structural_element->range_id);
        $payload['global_chat_model'] = GPTClient::getGlobalChatModel();
        $payload['text_block_summary'] = getCoursewareSummary($this->block);

        // Collect questions of block
        if ($payload['question_mode'] === 'pool') {
            $payload['block_questions'] = [];
            foreach (GPTQuestion::getQuestions($this->block->id) as $question) {
                $payload['block_questions'][] = $question->getAttributes($can_edit);
            }
        }

        return $payload;
    }

    public function setPayload($payload): void
    {
        $payload_old = json_decode($this->block->payload, true);

        // Store custom api key
        if ($payload['api_key_origin'] === 'custom' && !empty($payload['custom_api_key'])) {
            $range_id = $this->block->container->getStructuralElement()->range_id;
            GPTClient::storeCustomApiKey($range_id, $payload['custom_api_key']);
        }

        // Delete all questions if mode is switched
        if ($payload_old['question_mode'] !== $payload['question_mode']) {
            GPTQuestion::deleteQuestions($this->block->id);
        }

        // Handle question pool
        if ($payload['question_mode'] === 'pool') {
            if (empty($payload['block_questions'])) {
                // Initialize question pool if empty
                GPTQuestion::deleteQuestions($this->block->id);  // Ensure block has no questions
                GPTQuestion::generateQuestions($this->block, $payload['difficulty'], $payload['question_count']);
            } else {
                // Store edited questions in database
                GPTQuestion::setQuestions($this->block->id, $payload['block_questions']);
            }
        }

        unset($payload['custom_api_key']);
        unset($payload['block_questions']);

        parent::setPayload($payload);
    }
}
