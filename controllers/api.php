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
            // Check if question is related to block
            if ($q->block_id == $block_id) {
                $formatted_questions[] = "{$q->question}\n\n{$q->solution}";
            }
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
        $data = $this->requestOpenai($generate_question_prompt, $block, $block_payload);

        // Process OpenAi response
        $text = $data['choices'][0]['message']['content'];
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
            'click_date'                => $click_date,
            'mkdate'                    => time(),
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

    public function feedback_action($block_id)
    {
        $click_date = time();

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
            'question_id'   => $question->id,
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
        $data = $this->requestOpenai($generate_feedback_prompt, $block, $block_payload);

        // Process OpenAi response
        $feedback = $data['choices'][0]['message']['content'];

        // Store feedback
        $feedback_object = \CoursewareGPTBlock\GPTFeedback::create([
            'answer_id'  => $answer_object->id,
            'feedback'   => $feedback,
            'click_date' => $click_date,
            'mkdate'     => time(),
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
     * @param $block_payload array json decoded payload of passed block
     * @return mixed|null json decoded response
     * @throws Trails_Exception when the request fails
     */
    public function requestOpenai(string $prompt, \Courseware\Block $block, array $block_payload) {
        $chat_model = getGlobalChatModel();

        // Get the api key
        if ($block_payload['api_key_origin'] === 'global') {
            $api_key = getGlobalApiKey();
        } else {
            $api_key = getCustomApiKey($block);

            // Use custom chat model if own api key and model not empty
            if (!empty($block_payload['custom_chat_model'])) {
                $chat_model = $block_payload['custom_chat_model'];
            }
        }

        if (empty($api_key)) {
            throw new AccessDeniedException(dgettext('CoursewareGPTBlock', 'Bitte geben Sie im Block einen API-Key an.'));
        }

        // Send request to OpenAI
        $ch = curl_init('https://api.openai.com/v1/chat/completions');

        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_HEADER, false);
        curl_setopt($ch, CURLOPT_HTTPHEADER, [
            "Content-Type: application/json",
            "Authorization: Bearer {$api_key}"
        ]);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);

        $post_body = json_encode([
            'model' => $chat_model,
            'messages' => [
                [
                    'role' => 'user',
                    'content' => $prompt,
                ],
            ],
            'max_tokens' => 500,
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
            $error_response = json_decode($response, true);

            $error_msg = dgettext('CoursewareGPTBlock', "OpenAI-API-Fehler: {$error_response['error']['message']}.");
            if ($error_response['error']['code'] === 'invalid_api_key') {
                $error_msg = dgettext('CoursewareGPTBlock', 'Der OpenAI-API-Key ist fehlerhaft.');
            }
            if ($error_response['error']['code'] === 'model_not_found') {
                $error_msg = dgettext('CoursewareGPTBlock', 'Das OpenAI-Model konnte nicht gefunden werden.');
            }
            throw new Trails_Exception(500, $error_msg);
        }

        return json_decode($response, true);
    }

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
            'mkdate'        => time(),
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
