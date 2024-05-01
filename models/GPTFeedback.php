<?php

namespace CoursewareGPTBlock;

require_once 'vendor/exTpl/Template.php';

use exTpl\Template;

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

    public static function generateFeedback(\Courseware\Block $block, GPTQuestion $question, GPTUserAnswer $answer): GPTFeedback
    {
        $click_date = time();

        $block_payload = json_decode($block->payload, true);
        $language = $block_payload['language'];

        Template::setTagMarkers('{{', '}}');
        $feedback_prompt = \Config::get()->getValue('COURSEWARE_GPT_FEEDBACK_PROMPT')->localized($language);
        $template = new Template($feedback_prompt);
        $generate_feedback_prompt = $template->render([
            'question' => $question->question,
            'answer' => $answer->answer,
            'solution' => $question->solution,
        ]);

        // Send request to OpenAI
        $data = sendPrompt($generate_feedback_prompt, $block, $block_payload);

        // Process OpenAi response
        $feedback = $data['choices'][0]['message']['content'];

        // Store feedback
        return \CoursewareGPTBlock\GPTFeedback::create([
            'answer_id'  => $answer->id,
            'feedback'   => $feedback,
            'click_date' => $click_date,
        ]);
    }
}
