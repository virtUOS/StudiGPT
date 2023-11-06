<?php

namespace CoursewareGPTBlock;

/**
 * User feedback to generated question or feedback
 *
 * @property string                                     $id                 database column
 * @property string                                     $range_id           database column
 * @property string                                     $range_type         database column
 * @property string                                     $value              database column
 * @property int                                        $mkdate             database column
 * @property string                                     $user_id            database column
 * @property \User                                      $user               belongs_to User
 * @property ?\CoursewareGPTBlock\GPTQuestion           $question           belongs_to CoursewareGPTBlock\GPTQuestion
 * @property ?\CoursewareGPTBlock\GPTFeedback           $feedback           belongs_to CoursewareGPTBlock\GPTFeedback
 */
class GPTUserFeedback extends \SimpleORMap
{
    protected static function configure($config = [])
    {
        $config['db_table'] = 'gpt_user_feedback';

        $config['has_one']['user_feedback'] = [
            'class_name' => GPTUserFeedback::class,
            'assoc_foreign_key' => 'range_id',
            'on_delete' => 'delete',
            'on_store' => 'store',
        ];

        $config['belongs_to']['user'] = [
            'class_name' => \User::class,
            'foreign_key' => 'user_id'
        ];
        $config['belongs_to']['question'] = [
            'class_name' => GPTQuestion::class,
            'foreign_key' => 'range_id'
        ];
        $config['belongs_to']['feedback'] = [
            'class_name' => GPTFeedback::class,
            'foreign_key' => 'range_id'
        ];

        parent::configure($config);
    }
}