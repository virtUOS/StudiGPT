<?php

namespace CoursewareGPTBlock;

require_once 'vendor/exTpl/Template.php';

use exTpl\Template;

/**
 * Generated Question with corresponding solution
 *
 * @property int                            $id                 database column
 * @property string                         $question           database column
 * @property string                         $solution           database column
 * @property string                         $difficulty         database column
 * @property string                         $language           database column
 * @property string                         $click_date         database column
 * @property int                            $mkdate             database column
 * @property int                            $block_id           database column
 * @property string                         $course_id          database column
 * @property \SimpleORMapCollection         $user_answers       has_many CoursewareGPTBlock\GPTUserAnswer
 * @property \SimpleORMapCollection         $user_feedbacks     has_many CoursewareGPTBlock\GPTUserFeedback
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

    /**
     * Generates new questions using an LLM model
     *
     * @param \Courseware\Block $block block to which the generated questions belong
     * @param string $difficulty difficulty of the question
     * @param array $previous_questions previous questions to prevent equal questions
     * @param int $number number of questions to generate
     *
     * @return GPTQuestion[] generated questions
     *
     * @throws \Trails_Exception
     */
    public static function generateQuestions(\Courseware\Block $block, string $difficulty, int $number, array $previous_questions = []): array
    {
        $click_date = time();

        $structural_element = $block->container->getStructuralElement();
        $block_payload = json_decode($block->payload, true);

        $additional_instructions = $block_payload['additional_instructions'];
        $language = $block_payload['language'] ?? 'de_DE';

        // Get course title or title of learning content
        if ($structural_element->range_type == 'course') {
            $course = $structural_element->course;
            $title = $course->name;
        } else {
            $root_courseware = \Courseware\StructuralElement::getCoursewareUser($structural_element->range_id);
            $title = $root_courseware->title;
        }

        // Collect courseware site text content
        if ($block_payload['use_block_contents']) {
            $summary = getCoursewareSummary($block);
        } else {
            $summary = $block_payload['summary'];
        }

        // Format questions properly
        $old_questions = [];
        foreach ($previous_questions as $question) {
            $q = \CoursewareGPTBlock\GPTQuestion::find($question['id']);
            // Check if question is related to block
            if ($q->block_id == $block->id) {
                $old_questions[] = [
                    'question' => $q->question,
                    'solution' => $q->solution
                ];
            }
        }
        $questions_json = json_encode($old_questions);

        // Render template
        Template::setTagMarkers('{{', '}}');
        $question_prompt = \Config::get()->getValue('COURSEWARE_GPT_QUESTION_PROMPT')->localized($language);

        $template = new Template($question_prompt);
        $generate_question_prompt = $template->render([
            'title' => $title,
            'summary' => $summary,
            'additional_instructions' => $additional_instructions,
            'difficulty' => $difficulty,
            'number' => $number,
            'questions' => $questions_json
        ]);

        // Send request to OpenAI
        $data = sendPrompt($generate_question_prompt, $block, $block_payload);

        // Process OpenAi response
        $text = $data['choices'][0]['message']['content'];

        $generated_questions = json_decode($text);
        if (!is_array($generated_questions)) {
            // Ensure data is array
            $generated_questions = [$generated_questions];
        }

        $question_objects = [];

        // Store generated questions in database
        foreach ($generated_questions as $generated_question) {
            $question_objects[] = \CoursewareGPTBlock\GPTQuestion::create([
                'question'                  => $generated_question->question,
                'solution'                  => $generated_question->solution,
                'difficulty'                => $difficulty,
                'additional_instructions'   => $additional_instructions,
                'language'                  => $language,
                'click_date'                => $click_date,
                'block_id'                  => $block->id,
                'course_id'                 => isset($course) ? $course->id : null,
            ]);
        }

        return $question_objects;
    }
    /**
     * Get all questions of a block
     *
     * @param string $block_id
     * @return GPTQuestion[]
     */
    public static function getQuestions(string $block_id): array
    {
        return static::findBySQL('block_id = ?', [$block_id]);
    }

    /**
     * Delete all questions of a block
     *
     * @param string $block_id
     */
    public static function deleteQuestions(string $block_id) {
        static::deleteBySQL('block_id = ?', [$block_id]);
    }

    /**
     * Get attributes of question
     *
     * @param bool $include_feedbacks include user feedback statistics in attributes
     * @return array question attributes
     */
    public function getAttributes(bool $include_feedbacks = false): array
    {
        $attributes = [
            'id' => $this->id,
            'question'   => $this->question,
            'solution'   => $this->solution,
            'difficulty' => $this->difficulty,
        ];

        if ($include_feedbacks) {
            $user_feedback_statistics = GPTUserFeedback::getStatistics($this->id);

            $attributes['likes'] = $user_feedback_statistics['likes'];
            $attributes['dislikes'] = $user_feedback_statistics['dislikes'];
        }

        return $attributes;
    }

    /**
     * Set questions in database for a given block
     *
     * @param string $block_id block id
     * @param array $questions array of question attributes
     * @return void
     */
    public static function setQuestions(string $block_id, array $questions)
    {
        $question_objects = static::getQuestions($block_id);

        // Delete questions in db
        foreach ($question_objects as $question_object) {
            // Search for question in passed parameter
            $found = false;
            foreach ($questions as $question) {
                if ($question['id'] === $question_object->id) {
                    $found = true;
                    break;
                }
            }

            // Question not found
            if (!$found) {
                $question_object->delete();
            }
        }

        // Update questions
        foreach ($questions as $question) {
            $question_object = GPTQuestion::find($question['id']);

            if ($question_object) {
                $question_object->question = $question['question'];
                $question_object->solution = $question['solution'];
                $question_object->difficulty = $question['difficulty'];
                $question_object->store();
            }
        }
    }
}
