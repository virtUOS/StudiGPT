<?php

class CleanupDb extends Migration {

    function description()
    {
        return 'Cleanup DB by adding enums, renaming columns and removing unnecessary prompt column';
    }

    public function up()
    {
        $db = DBManager::get();

        $sql = "ALTER TABLE `gpt_question` 
            CHANGE COLUMN `difficulty` `difficulty` ENUM('easy', 'hard') COLLATE latin1_bin NOT NULL,
            CHANGE COLUMN `language` `language` ENUM('de_DE', 'en_GB') COLLATE latin1_bin NOT NULL,
            CHANGE COLUMN `generated_date` `mkdate` int(11) NOT NULL,
            DROP COLUMN `prompt`
        ";
        $db->exec($sql);

        $sql = "ALTER TABLE `gpt_feedback` 
            CHANGE COLUMN `generated_date` `mkdate` int(11) NOT NULL,
            DROP COLUMN `prompt`
        ";
        $db->exec($sql);

        $sql = "UPDATE `gpt_user_feedback`
            SET `value` = 1 
            WHERE `value` = 'like' 
        ";
        $db->exec($sql);

        $sql = "UPDATE `gpt_user_feedback`
            SET `value` = 0 
            WHERE `value` = 'dislike' 
        ";
        $db->exec($sql);

        $sql = "ALTER TABLE `gpt_user_feedback` 
            CHANGE COLUMN `value` `value` tinyint(1) NOT NULL
        ";
        $db->exec($sql);
    }

    public function down()
    {
        $db = DBManager::get();

        $sql = "ALTER TABLE `gpt_question` 
            CHANGE COLUMN `difficulty` `difficulty` varchar(64) NOT NULL,
            CHANGE COLUMN `language` `language` varchar(64) NOT NULL,
            CHANGE COLUMN `mkdate` `generated_date` int(11) NOT NULL,
            ADD COLUMN `prompt` text NOT NULL
        ";
        $db->exec($sql);

        $sql = "ALTER TABLE `gpt_feedback` 
            CHANGE COLUMN `mkdate` `generated_date` int(11) NOT NULL,
            ADD COLUMN `prompt` text NOT NULL
        ";
        $db->exec($sql);

        $sql = "ALTER TABLE `gpt_user_feedback` 
            CHANGE COLUMN `value` `value` tinytext NOT NULL
        ";
        $db->exec($sql);
    }
}
