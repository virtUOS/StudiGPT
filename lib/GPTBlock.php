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

        $payload['has_api_key'] = !empty(getApiKey($this->block));
        $payload['text_block_summary'] = getCoursewareSummary($this->block);

        return $payload;
    }

    public function setPayload($payload): void
    {
        if (!empty($payload['api_key'])) {
            storeApiKey($this->block, $payload['api_key']);
        }

        unset($payload['api_key']);
        parent::setPayload($payload);
    }
}
