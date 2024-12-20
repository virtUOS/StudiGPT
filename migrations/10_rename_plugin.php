<?php

class RenamePlugin extends Migration
{
    const CONFIGURATIONS = [
        'COURSEWARE_GPT_API_KEY',
        'COURSEWARE_GPT_CHAT_MODEL',
        'COURSEWARE_GPT_CUSTOM_API_KEY',
        'COURSEWARE_GPT_CUSTOM_ENDPOINT',
        'COURSEWARE_GPT_ENDPOINT',
        'COURSEWARE_GPT_FEEDBACK_PROMPT',
        'COURSEWARE_GPT_QUESTION_PROMPT',
    ];

    function description()
    {
        return 'Rename plugin to KI-Quiz';
    }

    public function up()
    {
        // Change config section
        foreach (self::CONFIGURATIONS as $key) {
            $config = ConfigEntry::find($key);
            $config->section = 'KI-Quiz';
            $config->store();
        }

        $db = DBManager::get();

        // Disable migrations for old plugin to prevent accidental deletion of data
        $db->exec("DELETE FROM schema_version WHERE domain = 'StudiGPT'");
    }

    public function down()
    {
        // No way back to old name
    }
}
