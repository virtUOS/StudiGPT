<?php

class ApiController extends PluginController
{
    /**
     * Generates new questions using an LLM model
     *
     * @param $block_id
     * @return void
     * @throws AccessDeniedException
     */
    public function question_action($block_id)
    {
        $block = \Courseware\Block::find($block_id);
        $structural_element = $block->container->getStructuralElement();
        $user = \User::findCurrent();

        // Check permissions
        if (!$structural_element->canRead($user)) {
            throw new AccessDeniedException(dgettext('CoursewareGPTBlock', 'Sie verfügen nicht über die notwendigen Rechte für diese Aktion.'));
        }

        $difficulty = Request::get('difficulty');
        $questions = json_decode(Request::get('questions'), true);
        $number = Request::int('number', 1);

        $generated_questions = \CoursewareGPTBlock\GPTQuestion::generateQuestions($block, $difficulty, $number, $questions);
        $response = [];

        foreach ($generated_questions as $generated_question) {
            $response[] = [
                'id' => $generated_question->id,
                'question' => $generated_question->question,
                'solution' => $generated_question->solution,
                'difficulty' => $generated_question->difficulty,
            ];
        }

        $this->render_json($response);
    }

    /**
     * Generates feedback for a user question answer using an LLM model
     *
     * @param $block_id
     * @return void
     * @throws AccessDeniedException
     */
    public function feedback_action($block_id)
    {
        $block = \Courseware\Block::find($block_id);
        $user = \User::findCurrent();

        // Check permissions
        if (!$block->container->getStructuralElement()->canRead($user)) {
            throw new AccessDeniedException(dgettext('CoursewareGPTBlock', 'Sie verfügen nicht über die notwendigen Rechte für diese Aktion.'));
        }

        $question_id = Request::get('question_id');
        $question = \CoursewareGPTBlock\GPTQuestion::find($question_id);

        // Question must be related to the block
        if ($question->block_id != $block_id) {
            throw new AccessDeniedException(dgettext('CoursewareGPTBlock', 'Sie verfügen nicht über die notwendigen Rechte für diese Aktion.'));
        }

        $answer = Request::get('answer', 'No answer');

        // Store user answer
        $answer_object = \CoursewareGPTBlock\GPTUserAnswer::create([
            'answer'        => $answer,
            'question_id'   => $question->id,
        ]);

        // Generate feedback
        $feedback_object = \CoursewareGPTBlock\GPTFeedback::generateFeedback($block, $question, $answer_object);

        $this->render_json([
            'id' => $feedback_object->id,
            'feedback' => $feedback_object->feedback,
        ]);
    }

    /**
     * Stores user feedback for a generated question or feedback
     *
     * @param $block_id
     * @return void
     * @throws AccessDeniedException
     */
    public function user_feedback_action($block_id)
    {
        $user_feedback_id = Request::get('user_feedback_id');
        $range_id = Request::get('range_id');
        $range_type = Request::get('range_type');
        $feedback_value = Request::int('feedback_value');

        $block = \Courseware\Block::find($block_id);
        $user = \User::findCurrent();

        // Check permissions
        if (!$block->container->getStructuralElement()->canRead($user)) {
            throw new AccessDeniedException(dgettext('CoursewareGPTBlock', 'Sie verfügen nicht über die notwendigen Rechte für diese Aktion.'));
        }

        // Check if range object (question, feedback) is related to block
        $is_range_in_block = false;
        if ($range_type === 'question') {
            $question_object = \CoursewareGPTBlock\GPTQuestion::find($range_id);
            $is_range_in_block = $question_object->block_id == $block_id;
        } elseif ($range_type === 'feedback') {
            $feedback_object = \CoursewareGPTBlock\GPTFeedback::find($range_id);
            $is_range_in_block = $feedback_object->user_answer->question->block_id == $block_id;
        }

        if (!$is_range_in_block) {
            throw new AccessDeniedException(dgettext('CoursewareGPTBlock', 'Sie verfügen nicht über die notwendigen Rechte für diese Aktion.'));
        }

        $user_feedback_object = null;
        if (!empty($user_feedback_id)) {
            // Find existing user feedback of given range object
            $user_feedback_object = \CoursewareGPTBlock\GPTUserFeedback::findOneBySQL(
                'id = ? AND range_id = ? AND range_type = ?',
                [$user_feedback_id, $range_id, $range_type]
            );
        }
        if (empty($user_feedback_object)) {
            // Create new user feedback
            $user_feedback_object = new \CoursewareGPTBlock\GPTUserFeedback();
        }

        $user_feedback_object->setData([
            'range_id'      => $range_id,
            'range_type'    => $range_type,
            'value'         => $feedback_value,
        ]);
        $user_feedback_object->store();

        $response = $user_feedback_object->toArray();
        $response['value'] = (int) $response['value'];  // Ensure value is integer

        $this->render_json($response);
    }

    /**
     * Exports gpt block statistics as JSON
     */
    /*
    public function export_json_action() {
        $user = \User::findCurrent();

        // Check role
        if (!$user->hasRole('CoursewareGPTEvaluator')) {
            throw new AccessDeniedException(dgettext('CoursewareGPTBlock', 'Sie verfügen nicht über die notwendigen Rechte für diese Aktion.'));
        }

        $results = ['questions' => []];

        $questions = \CoursewareGPTBlock\GPTQuestion::findBySQL('1');

        foreach ($questions as $question) {
            // Collect user feedbacks for question
            $user_feedbacks = [];
            foreach ($question->user_feedbacks as $user_feedback) {
                $user_feedbacks[] = [
                    'user_id' => hash('sha256', $user_feedback->user_id),
                    'feedback' => $user_feedback->value,
                    'mkdate' => $user_feedback->mkdate,
                ];
            }

            // Collect user answers of question
            $user_answers = [];
            foreach ($question->user_answers as $user_answer_object) {
                // Collect generated feedback of user answer
                $feedback = null;
                $feedback_object = $user_answer_object->feedback;
                if ($feedback_object) {
                    // Collect user feedback to feedback
                    $user_feedback = null;
                    $user_feedback_object = $feedback_object->user_feedback;
                    if ($user_feedback_object) {
                        $user_feedback = [
                            'user_id' => hash('sha256', $user_feedback_object->user_id),
                            'feedback' => $user_feedback_object->value,
                            'mkdate' => $user_feedback_object->mkdate,
                        ];
                    }

                    $feedback = [
                        'feedback' => $feedback_object->feedback,
                        'prompt' => $feedback_object->prompt,
                        'click_date' => $feedback_object->click_date,
                        'generated_date' => $feedback_object->generated_date,
                        'user_feedback' => $user_feedback,
                    ];
                }

                $user_answers[] = [
                    'id' => $user_answer_object->id,
                    'answer' => $user_answer_object->answer,
                    'mkdate' => $user_answer_object->mkdate,
                    'user_id' => hash('sha256', $user_answer_object->user_id),
                    'feedback' => $feedback,
                ];
            }

            $results['questions'][] = [
                'id' => $question->id,
                'question' => $question->question,
                'solution' => $question->solution,
                'difficulty' => $question->difficulty,
                'language' => $question->language,
                'prompt' => $question->prompt,
                'click_date' => $question->click_date,
                'generated_date' => $question->generated_date,
                'creator_id' => hash('sha256', $question->creator_id),
                'course_name' => isset($question->course) ? $question->course->name : null,
                'user_feedbacks' => $user_feedbacks,
                'user_answers' => $user_answers,
            ];
        }

        $this->render_json($results);
    }
    */
}
