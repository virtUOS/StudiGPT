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

        // Check permissions
        if (!$structural_element->canRead($user)) {
            throw new AccessDeniedException(dgettext('CoursewareGPTBlock', 'Sie verfügen nicht über die notwendigen Rechte für diese Aktion.'));
        }

        $block_payload = json_decode($block->payload, true);

        $additional_instructions = $block_payload['additional_instructions'];

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
        foreach ($questions as $q) {
            list($question, $solution) = explode('?', $q, 2);
            $formatted_questions[] = "{$question}?\n\n{$solution}";
        }
        $questions_text = join("\n\n", $formatted_questions);

        // Render template
        Template::setTagMarkers('{{', '}}');
        $template = new Template(strval(Config::get()->getValue('COURSEWARE_GPT_QUESTION_PROMPT')));
        $generate_question_prompt = $template->render([
            'title' => $title,
            'summary' => $summary,
            'additional_instructions' => $additional_instructions,
            'difficulty' => $difficulty,
            'questions' => $questions_text
        ]);

        // Send request to OpenAI Process response
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

        $this->render_json([
            'question' => $question,
            'solution' => $solution,
            //'prompt' => $generate_question_prompt,  // TODO: Add when debugging mode is implemented
            //'openai_response' => $data,  // TODO: Add when debugging mode is implemented
        ]);
    }

    public function feedback_action($block_id)
    {
        $block = \Courseware\Block::find($block_id);
        $user = \User::findCurrent();

        // Check permissions
        if (!$block->container->getStructuralElement()->canRead($user)) {
            throw new AccessDeniedException(dgettext('CoursewareGPTBlock', 'Sie verfügen nicht über die notwendigen Rechte für diese Aktion.'));
        }

        $question = trim(Request::get('question'));
        $answer = Request::get('answer');
        $solution = Request::get('solution');

        if (empty($answer)) {
            $answer = "I don't know.";
        }

        Template::setTagMarkers('{{', '}}');
        $template = new Template(strval(Config::get()->getValue('COURSEWARE_GPT_FEEDBACK_PROMPT')));
        $generate_feedback_prompt = $template->render([
            'question' => $question,
            'answer' => $answer,
            'solution' => $solution,
        ]);

        // Send request to OpenAI
        $data = $this->requestOpenai($generate_feedback_prompt, $block);

        // Process OpenAi response
        $feedback = $data['choices'][0]['text'];

        $this->render_json([
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
}
