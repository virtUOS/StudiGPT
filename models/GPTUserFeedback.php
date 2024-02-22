<?php

namespace CoursewareGPTBlock;

/**
 * User feedback to generated question or feedback
 *
 * @property int                                        $id                 database column
 * @property int                                        $range_id           database column
 * @property string                                     $range_type         database column
 * @property string                                     $value              database column
 * @property int                                        $mkdate             database column
 * @property ?\CoursewareGPTBlock\GPTQuestion           $question           belongs_to CoursewareGPTBlock\GPTQuestion
 * @property ?\CoursewareGPTBlock\GPTFeedback           $feedback           belongs_to CoursewareGPTBlock\GPTFeedback
 */
class GPTUserFeedback extends \SimpleORMap
{
    protected static function configure($config = [])
    {
        $config['db_table'] = 'gpt_user_feedback';

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