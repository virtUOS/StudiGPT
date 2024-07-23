<?php

class AddConfigurableEndpoint extends Migration {

    function description()
    {
        return 'Add global and range config for endpoint';
    }

    public function up()
    {
        $cfg = Config::get();

        // Config for global endpoint
        $cfg->create('COURSEWARE_GPT_ENDPOINT',
            [
                'value' => 'https://api.openai.com/v1/chat/completions',
                'type' => 'string',
                'range' => 'global',
                'section' => 'StudiGPT',
                'description' => 'Der globale Endpunkt der LLM-API. Dieser Endpunkt muss mit dem Chat-Completions-Endpunkt von OpenAI kompatibel sein.'
            ]
        );

        // Config for custom endpoint
        $cfg->create('COURSEWARE_GPT_CUSTOM_ENDPOINT',
            [
                'type' => 'string',
                'range' => 'range',
                'section' => 'StudiGPT',
                'description' => 'Der benutzerdefinierte Endpunkt eines Kurses oder einer Person, der mit dem Chat-Completions-Endpunkt von OpenAI kompatibel ist.'
            ]
        );
    }

    public function down()
    {
        $cfg = Config::get();

        $cfg->delete('COURSEWARE_GPT_ENDPOINT');
        $cfg->delete('COURSEWARE_GPT_CUSTOM_ENDPOINT');
    }
}
