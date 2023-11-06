<?php

class SetupDb extends Migration
{
    function description()
    {
        return 'initial database setup for studiGPT plugin';
    }

    function up()
    {
        $db = DBManager::get();

        $sql = "CREATE TABLE `gpt_question` (
                `id` char(32) CHARACTER SET latin1 COLLATE latin1_bin NOT NULL,
                `question` text NOT NULL,
                `solution` text NOT NULL,
                `difficulty` varchar(64) NOT NULL,
                `language` varchar(64) NOT NULL,
                `prompt` text NOT NULL,
                `click_date` int(11) NOT NULL,
                `generated_date` int(11) NOT NULL,
                `creator_id` char(32) CHARACTER SET latin1 COLLATE latin1_bin NOT NULL,
                `block_id` int(11) NOT NULL,
                `course_id` char(32) CHARACTER SET latin1 COLLATE latin1_bin,
                PRIMARY KEY (`id`),
                KEY `block_id` (`block_id`),
                KEY `course_id` (`course_id`)
        )";
        $db->exec($sql);

        $sql = "CREATE TABLE `gpt_user_answer` (
                `id` char(32) CHARACTER SET latin1 COLLATE latin1_bin NOT NULL,
                `answer` text NOT NULL,
                `mkdate` int(11) NOT NULL,
                `question_id` char(32) CHARACTER SET latin1 COLLATE latin1_bin NOT NULL,
                `user_id` char(32) CHARACTER SET latin1 COLLATE latin1_bin NOT NULL,
                PRIMARY KEY (`id`),
                KEY `question_id` (`question_id`),
                KEY `user_id` (`user_id`)
        )";
        $db->exec($sql);

        $sql = "CREATE TABLE `gpt_feedback` (
                `answer_id` char(32) CHARACTER SET latin1 COLLATE latin1_bin NOT NULL,
                `feedback` text NOT NULL,
                `prompt` text NOT NULL,
                `click_date` int(11) NOT NULL,
                `generated_date` int(11) NOT NULL,                
                PRIMARY KEY (`answer_id`)
        )";
        $db->exec($sql);

        $sql = "CREATE TABLE `gpt_user_feedback` (
                `range_id` char(32) CHARACTER SET latin1 COLLATE latin1_bin NOT NULL,
                `range_type` ENUM('question', 'feedback') COLLATE latin1_bin,
                `user_id` char(32) CHARACTER SET latin1 COLLATE latin1_bin NOT NULL,
                `value` tinytext NOT NULL,
                `mkdate` int(11) NOT NULL,
                PRIMARY KEY (`range_id`, `range_type`, `user_id`),
                KEY `range_id` (`range_id`),
                KEY `range_type` (`range_type`),
                KEY `user_id` (`range_type`)
        )";
        $db->exec($sql);
    }

    function down()
    {
        $db = DBManager::get();

        $db->exec('DROP TABLE gpt_question, gpt_user_answer, gpt_feedback, gpt_user_feedback');
    }
}
