<?php

namespace CoursewareGPTBlock;

/**
 * User feedback to generated question or feedback
 *
 * @property int                                        $id                 database column
 * @property int                                        $range_id           database column
 * @property string                                     $range_type         database column
 * @property int                                        $value              database column
 * @property int                                        $mkdate             database column
 * @property ?\CoursewareGPTBlock\GPTQuestion           $question           belongs_to CoursewareGPTBlock\GPTQuestion
 * @property ?\CoursewareGPTBlock\GPTFeedback           $feedback           belongs_to CoursewareGPTBlock\GPTFeedback
 */
class GPTUserFeedback extends \SimpleORMap
{
    const LIKE = 1;
    const DISLIKE = 0;

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

    /**
     * Get statistics to given range object containing number of likes and dislikes
     *
     * @param string $range_id id of question or feedback
     * @return int[] [likes: int, dislikes: int]: number of likes and dislikes
     */
    public static function getStatistics(string $range_id): array
    {
        $user_feedbacks = static::findBySQL("range_id = ?", [$range_id]);

        $likes = 0;
        $dislikes = 0;
        foreach ($user_feedbacks as $feedback) {
            switch ($feedback->value) {
                case self::LIKE:
                    $likes++;
                    break;
                case self::DISLIKE:
                    $dislikes++;
                    break;
            }
        }

        return [
            'likes' => $likes,
            'dislikes' => $dislikes,
        ];
    }
}