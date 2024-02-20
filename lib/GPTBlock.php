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

        $payload['has_global_api_key'] = !empty(getGlobalApiKey());
        $payload['has_custom_api_key'] = !empty(getCustomApiKey($this->block));
        $payload['global_chat_model'] = getGlobalChatModel();
        $payload['text_block_summary'] = getCoursewareSummary($this->block);

        return $payload;
    }

    public function setPayload($payload): void
    {
        if ($payload['api_key_origin'] === 'custom' && !empty($payload['custom_api_key'])) {
            storeCustomApiKey($this->block, $payload['custom_api_key']);
        }

        unset($payload['custom_api_key']);
        parent::setPayload($payload);
    }
}
