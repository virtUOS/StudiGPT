<?php

namespace CoursewareGPTBlock;

/**
 * Generated Question with corresponding solution
 *
 * @property string                         $id                 database column
 * @property string                         $question           database column
 * @property string                         $solution           database column
 * @property string                         $difficulty         database column
 * @property string                         $language           database column
 * @property string                         $prompt             database column
 * @property string                         $click_date         database column
 * @property int                            $generated_date     database column
 * @property string                         $creator_id         database column
 * @property int                            $block_id           database column
 * @property string                         $course_id          database column
 * @property \SimpleORMapCollection         $user_answers       has_many CoursewareGPTBlock\GPTUserAnswer
 * @property \SimpleORMapCollection         $user_feedbacks     has_many CoursewareGPTBlock\GPTUserFeedback
 * @property \User                          $creator            belongs_to User
 * @property \Courseware\Block              $block              belongs_to Courseware\Block
 * @property ?\Course                       $course             belongs_to Course
 */
class GPTQuestion extends \SimpleORMap
{
    protected static function configure($config = [])
    {
        $config['db_table'] = 'gpt_question';

        $config['has_many']['user_answers'] = [
            'class_name' => GPTUserAnswer::class,
            'assoc_foreign_key' => 'question_id',
            'on_delete' => 'delete',
            'on_store' => 'store'
        ];
        $config['has_many']['user_feedbacks'] = [
            'class_name' => GPTUserFeedback::class,
            'assoc_foreign_key' => 'range_id',
            'on_delete' => 'delete',
            'on_store' => 'store'
        ];

        $config['belongs_to']['creator'] = [
            'class_name' => \User::class,
            'foreign_key' => 'creator_id'
        ];
        $config['belongs_to']['block'] = [
            'class_name' => \Courseware\Block::class,
            'foreign_key' => 'block_id'
        ];
        $config['belongs_to']['course'] = [
            'class_name' => \Course::class,
            'foreign_key' => 'course_id'
        ];

        parent::configure($config);
    }
}