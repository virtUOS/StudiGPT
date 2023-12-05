<?php

require_once 'vendor/exTpl/Template.php';

use exTpl\Template;

class ApiController extends PluginController
{
    public function question_action($block_id)
    {
        $block = \Courseware\Block::find($block_id);
        $structural_element = $block->container->getStructuralElement();
        $user = \User::findCurrent();

        $click_date = time();

        // Check permissions
        if (!$structural_element->canRead($user)) {
            throw new AccessDeniedException(dgettext('CoursewareGPTBlock', 'Sie verfügen nicht über die notwendigen Rechte für diese Aktion.'));
        }

        $block_payload = json_decode($block->payload, true);

        $additional_instructions = $block_payload['additional_instructions'];
        $language = $block_payload['language'];

        $difficulty = Request::get('difficulty');
        $questions = json_decode(Request::get('questions'));

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
        $formatted_questions = [];
        foreach ($questions as $id) {
            $q = \CoursewareGPTBlock\GPTQuestion::find($id);
            $formatted_questions[] = "{$q->question}\n\n{$q->solution}";
        }
        $questions_text = join("\n\n", $formatted_questions);

        // Render template
        Template::setTagMarkers('{{', '}}');
        $question_prompt = Config::get()->getValue('COURSEWARE_GPT_QUESTION_PROMPT')->localized($language);
        $template = new Template($question_prompt);
        $generate_question_prompt = $template->render([
            'title' => $title,
            'summary' => $summary,
            'additional_instructions' => $additional_instructions,
            'difficulty' => $difficulty,
            'questions' => $questions_text
        ]);

        // Send request to OpenAI
        $data = $this->requestOpenai($generate_question_prompt, $block);

        // Process OpenAi response
        $text = $data['choices'][0]['text'];
        $text_lines = array_map('trim', explode("\n", $text));

        // Get the last question and answer
        if (!empty($text_lines[count($text_lines) - 2])) {
            $question = $text_lines[count($text_lines) - 2];
            $solution = $text_lines[count($text_lines) - 1];
        } else {
            $question = $text_lines[count($text_lines) - 3];
            $solution = $text_lines[count($text_lines) - 1];
        }

        // Store generated question in database
        $question_object = \CoursewareGPTBlock\GPTQuestion::create([
            'question'                  => $question,
            'solution'                  => $solution,
            'difficulty'                => $difficulty,
            'additional_instructions'   => $additional_instructions,
            'language'                  => $language,
            'prompt'                    => $generate_question_prompt,
            'click_date'                => $click_date,
            'generated_date'            => time(),
            'creator_id'                => $user->id,
            'block_id'                  => $block_id,
            'course_id'                 => isset($course) ? $course->id : null,
        ]);

        $this->render_json([
            'id' => $question_object->id,
            'question' => $question,
            'solution' => $solution,
            //'prompt' => $generate_question_prompt,  // TODO: Add when debugging mode is implemented
            //'openai_response' => $data,  // TODO: Add when debugging mode is implemented
        ]);
    }

    public function feedback_action($question_id)
    {
        $question = \CoursewareGPTBlock\GPTQuestion::find($question_id);
        $block = \Courseware\Block::find($question->block_id);
        $user = \User::findCurrent();

        $click_date = time();

        // Check permissions
        if (!$block->container->getStructuralElement()->canRead($user)) {
            throw new AccessDeniedException(dgettext('CoursewareGPTBlock', 'Sie verfügen nicht über die notwendigen Rechte für diese Aktion.'));
        }

        $block_payload = json_decode($block->payload, true);
        $language = $block_payload['language'];

        $answer = Request::get('answer');

        if (empty($answer)) {
            $answer = "I don't know.";
        }

        // Store user answer
        $answer_object = \CoursewareGPTBlock\GPTUserAnswer::create([
            'answer'        => $answer,
            'mkdate'        => time(),
            'question_id'   => $question_id,
            'user_id'       => $user->id,
        ]);

        Template::setTagMarkers('{{', '}}');
        $feedback_prompt = Config::get()->getValue('COURSEWARE_GPT_FEEDBACK_PROMPT')->localized($language);
        $template = new Template($feedback_prompt);
        $generate_feedback_prompt = $template->render([
            'question' => $question->question,
            'answer' => $answer,
            'solution' => $question->solution,
        ]);

        // Send request to OpenAI
        $data = $this->requestOpenai($generate_feedback_prompt, $block);

        // Process OpenAi response
        $feedback = $data['choices'][0]['text'];

        // Store feedback
        $feedback_object = \CoursewareGPTBlock\GPTFeedback::create([
            'answer_id'         => $answer_object->id,
            'feedback'          => $feedback,
            'prompt'            => $generate_feedback_prompt,
            'click_date'        => $click_date,
            'generated_date'    => time(),
        ]);

        $this->render_json([
            'id' => $feedback_object->id,
            'feedback' => $feedback,
            //'prompt' => $generate_feedback_prompt,  // TODO: Add when debugging mode is implemented
            //'openai_response' => $data,  // TODO: Add when debugging mode is implemented
        ]);
    }

    /**
     * Sends a request to the OpenAI API
     * @param $prompt string openai prompt
     * @return mixed|null json decoded response
     * @throws Trails_Exception when the request fails
     */
    public function requestOpenai(string $prompt, \Courseware\Block $block) {
        $api_key = getApiKey($block);

        if (empty($api_key)) {
            throw new AccessDeniedException(dgettext('CoursewareGPTBlock', 'Bitte geben Sie im Block einen API-Key an.'));
        }

        // Send request to OpenAI
        $ch = curl_init('https://api.openai.com/v1/completions');

        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_HEADER, false);
        curl_setopt($ch, CURLOPT_HTTPHEADER, [
            "Content-Type: application/json",
            "Authorization: Bearer {$api_key}"
        ]);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);

        $post_body = json_encode([
            'model' => 'text-davinci-003',
            'prompt' => $prompt,
            'max_tokens' => 150,
            'temperature' => 0.9,
            'top_p' => 1,
            'frequency_penalty' => 0,
            'presence_penalty' => 0,
        ]);
        curl_setopt($ch, CURLOPT_POSTFIELDS, $post_body);

        $response = curl_exec($ch);
        $http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);

        $error = curl_error($ch);
        curl_close($ch);
        if ($error || $http_code >= 400) {
            $error_msg = dgettext('CoursewareGPTBlock', 'Fehler bei OpenAI-API-Anfrage aufgetreten');
            if (mb_strpos($response, 'invalid_api_key') !== false) {
                $error_msg = dgettext('CoursewareGPTBlock', 'Der OpenAI-API-Key ist fehlerhaft.');
            }
            throw new Trails_Exception(500, $error_msg);
        }

        return json_decode($response, true);
    }

    public function user_feedback_action($block_id)
    {
        $range_id = Request::get('range_id');
        $range_type = Request::get('range_type');

        $block = \Courseware\Block::find($block_id);
        $user = \User::findCurrent();

        // Check permissions
        if (!$block->container->getStructuralElement()->canRead($user)) {
            throw new AccessDeniedException(dgettext('CoursewareGPTBlock', 'Sie verfügen nicht über die notwendigen Rechte für diese Aktion.'));
        }

        $feedback_value = Request::get('feedback_value');

        // Find existing user feedback
        $user_feedback = \CoursewareGPTBlock\GPTUserFeedback::findOneBySQL("range_id = ? AND range_type = ? AND user_id = ?", [$range_id, $range_type, $user->id]);
        if (!$user_feedback) {
            // Create new
            \CoursewareGPTBlock\GPTUserFeedback::create([
                'range_id'      => $range_id,
                'range_type'    => $range_type,
                'value'         => $feedback_value,
                'mkdate'        => time(),
                'user_id'       => $user->id,
            ]);
        } else {
            // Update value
            $user_feedback->value = $feedback_value;
            $user_feedback->store();
        }

        $this->render_nothing();
    }

    /**
     * Exports gpt block statistics as JSON
     */
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
}
