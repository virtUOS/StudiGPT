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

        // Rename old plugin
        $db->exec("REPLACE INTO plugins (pluginclassname, pluginpath, pluginname, plugintype, enabled) 
            SELECT 'KIQuiz' AS pluginclassname, 'virtUOS/KIQuiz' AS pluginpath, 'KI-Quiz' AS pluginname, plugintype, enabled
            FROM plugins WHERE pluginname = 'StudiGPT'
        ");

        // Move plugin schema
        $db->exec("UPDATE schema_version
            SET domain = 'KI-Quiz', version = version + 1
            WHERE domain = 'StudiGPT'
        ");

        // Copy plugin directory
        $studigpt_dir = __DIR__ . '/../../StudiGPT';
        $kiquiz_dir = __DIR__ . '/../../KIQuiz';
        if (is_dir($studigpt_dir) && !is_dir($kiquiz_dir)) {
            $this->recurseCopy($studigpt_dir, $kiquiz_dir);

            // Alter manifest in new plugin
            $manifest_dir = $kiquiz_dir . '/plugin.manifest';
            $manifest = file_get_contents($manifest_dir);
            $manifest = preg_replace('/^pluginname=.*$/m', 'pluginname=KI-Quiz', $manifest);
            $manifest = preg_replace('/^pluginclassname=.*$/m', 'pluginclassname=KIQuiz', $manifest);
            file_put_contents($manifest_dir, $manifest);
        }

        // Disable old plugin
        $db->exec("UPDATE plugins SET enabled = 'no' WHERE pluginclassname = 'StudiGPT'");
    }

    function recurseCopy($source, $dest)
    {
        $directory = opendir($source);

        if (!is_dir($dest)) {
            mkdir($dest);
        }

        while ($file = readdir($directory)) {
            if ($file === '.' || $file === '..') {
                continue;
            }

            if (is_dir("$source/$file")) {
                $this->recurseCopy("$source/$file", "$dest/$file");
            } else {
                copy("$source/$file", "$dest/$file");
            }
        }

        closedir($directory);
    }

    public function down()
    {
        $db = DBManager::get();
        $studigpt_installed = $db->fetchColumn("SELECT 1 FROM schema_version WHERE domain = 'StudiGPT' AND version > 0");
        $kiquiz_installed = $db->fetchColumn("SELECT 1 FROM schema_version WHERE domain = 'KI-Quiz' AND version > 0");

        // Prevent down migration if old and new plugins are installed
        if ($studigpt_installed && $kiquiz_installed) {
            throw new Exception('Could not down migrate this plugin because StudiGPT and KI-Quiz are installed and depend on each other. Please update KI-Quiz to a newer version (>= 0.2.1) and then remove StudiGPT.');
        }
    }
}
