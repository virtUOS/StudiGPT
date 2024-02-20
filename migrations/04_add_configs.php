<?php

class AddConfigs extends Migration {

    function description()
    {
        return 'Add global configs for api key and chat model, add StudiGPT config section and migrate payload of existing cw blocks';
    }

    public function up()
    {
        $cfg = Config::get();

        // Move existing configs to category StudiGPT
        $config = ConfigEntry::find('COURSEWARE_GPT_QUESTION_PROMPT');
        $config->section = 'StudiGPT';
        $config->store();

        $config = ConfigEntry::find('COURSEWARE_GPT_FEEDBACK_PROMPT');
        $config->section = 'StudiGPT';
        $config->store();

        // Rename COURSEWARE_GPT_API_KEY
        $config = ConfigEntry::find('COURSEWARE_GPT_API_KEY');
        $config->field = 'COURSEWARE_GPT_CUSTOM_API_KEY';
        $config->section = 'StudiGPT';
        $config->description = 'Der benutzerdefinierte OpenAI-API-Key eines Kurses oder einer Person.';
        $config->store();

        // Rename config values of COURSEWARE_GPT_API_KEY
        $config_values = ConfigValue::findBySQL("field = 'COURSEWARE_GPT_API_KEY'");
        foreach ($config_values as $value) {
            $value->field = 'COURSEWARE_GPT_CUSTOM_API_KEY';
            $value->store();
        }

        // Config for global api key
        $cfg->create('COURSEWARE_GPT_API_KEY',
            [
                'type' => 'string',
                'range' => 'global',
                'section' => 'StudiGPT',
                'description' => 'Der globale OpenAI-API-Key. Dieser kann im Courseware-Block neben einem benutzerdefinierten API-Key ausgewählt werden. Wenn kein globaler API-Key hinterlegt ist, kann das Plugin nur mit einem benutzerdefinierten API-Key verwendet werden.'
            ]
        );

        // Config for model
        $cfg->create('COURSEWARE_GPT_CHAT_MODEL',
            [
                'value' => 'gpt-3.5-turbo-0125',
                'type' => 'string',
                'range' => 'global',
                'section' => 'StudiGPT',
                'description' => "Das Chat Model von OpenAI. Das Model muss mit der Chat Completions API von OpenAI kompatibel sein. Dieses Model wird immer mit dem globalen API-Key verwendet. Benutzerdefinierte API-Keys können abweichende Modelle verwenden."

            ]
        );

        // Set api key origin 'custom' for existing blocks
        $gpt_blocks = \Courseware\Block::findBySQL("block_type = 'gpt'");
        foreach ($gpt_blocks as $block) {
            $block_payload = json_decode($block->payload, true);
            $block_payload['api_key_origin'] = 'custom';
            $block->payload = json_encode($block_payload);
            $block->store();
        }
    }

    public function down()
    {
        $cfg = Config::get();

        $cfg->delete('COURSEWARE_GPT_API_KEY');
        $cfg->delete('COURSEWARE_GPT_CHAT_MODEL');

        // Revert renaming
        $config = ConfigEntry::find('COURSEWARE_GPT_CUSTOM_API_KEY');
        $config->field = 'COURSEWARE_GPT_API_KEY';
        $config->store();

        $config_values = ConfigValue::findBySQL("field = 'COURSEWARE_GPT_CUSTOM_API_KEY'");
        foreach ($config_values as $value) {
            $value->field = 'COURSEWARE_GPT_API_KEY';
            $value->store();
        }
    }
}
