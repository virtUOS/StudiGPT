<?php

namespace CoursewareGPTBlock;

/**
 * Generated feedback to a user's answer
 *
 * @property int                                        $answer_id          database column
 * @property string                                     $id                 alias column for answer_id
 * @property string                                     $feedback           database column
 * @property int                                        $click_date         database column
 * @property int                                        $mkdate             database column
 * @property ?\CoursewareGPTBlock\GPTUserFeedback       $user_feedback      has_one CoursewareGPTBlock\GPTUserFeedback
 * @property \CoursewareGPTBlock\GPTUserAnswer          $user_answer        belongs_to CoursewareGPTBlock\GPTUserAnswer
 */
class GPTFeedback extends \SimpleORMap
{
    protected static function configure($config = [])
    {
        $config['db_table'] = 'gpt_feedback';

        $config['has_one']['user_feedback'] = [
            'class_name' => GPTUserFeedback::class,
            'assoc_foreign_key' => 'range_id',
            'on_delete' => 'delete',
            'on_store' => 'store',
        ];

        $config['belongs_to']['user_answer'] = [
            'class_name' => GPTUserAnswer::class,
            'foreign_key' => 'answer_id'
        ];

        parent::configure($config);
    }
}