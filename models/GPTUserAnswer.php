<?php

namespace CoursewareGPTBlock;

/**
 * User answer to a question
 *
 * @property int                                        $id                 database column
 * @property string                                     $answer             database column
 * @property int                                        $mkdate             database column
 * @property int                                        $question_id        database column
 * @property ?\CoursewareGPTBlock\GPTFeedback           $feedback           has_one CoursewareGPTBlock\GPTFeedback
 * @property \CoursewareGPTBlock\GPTQuestion            $question           belongs_to CoursewareGPTBlock\GPTQuestion
 */
class GPTUserAnswer extends \SimpleORMap
{
    protected static function configure($config = [])
    {
        $config['db_table'] = 'gpt_user_answer';

        $config['has_one']['feedback'] = [
            'class_name' => GPTFeedback::class,
            'assoc_foreign_key' => 'answer_id',
            'on_delete' => 'delete',
            'on_store' => 'store',
        ];

        $config['belongs_to']['question'] = [
            'class_name' => GPTQuestion::class,
            'foreign_key' => 'question_id'
        ];

        parent::configure($config);
    }
}