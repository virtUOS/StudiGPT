<?php

class RenameCleanup extends Migration
{
    function description()
    {
        return 'Remove schema version of old plugin';
    }

    public function up()
    {
        $db = DBManager::get();

        // Disable migrations for old plugin to prevent accidental deletion of data
        $db->exec("DELETE FROM schema_version WHERE domain = 'StudiGPT'");
    }

    public function down()
    {
        // No way back to old name
    }
}
