<?php

namespace CoursewareGPTBlock;

class OpenaiClient extends GPTClient {
    protected $temperature = 0.9;
    protected $top_p = 1;
    protected $frequency_penalty = 0;
    protected $presence_penalty = 0;

    public function request(string $prompt, string $api_key_origin, string $range_id, string $endpoint, string $chat_model)
    {
        $api_key = $this->getApiKey($api_key_origin, $range_id);

        if (empty($api_key)) {
            throw new \AccessDeniedException(dgettext('CoursewareGPTBlock', 'Bitte geben Sie im Block einen API-Key an.'));
        }

        // Send request to OpenAI
        $ch = curl_init($endpoint);

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
            'temperature' => $this->temperature,
            'top_p' => $this->top_p,
            'frequency_penalty' => $this->frequency_penalty,
            'presence_penalty' => $this->presence_penalty,
        ]);
        curl_setopt($ch, CURLOPT_POSTFIELDS, $post_body);

        $response = curl_exec($ch);
        $http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);

        $error = curl_error($ch);
        curl_close($ch);
        if ($error || $http_code >= 400) {
            $error_response = json_decode($response, true);

            if ($error_response && $error_response['error']) {
                // OpenAI-Error message handling
                $error_msg = dgettext('CoursewareGPTBlock', "OpenAI-API-Fehler: {$error_response['error']['message']}.");
                if ($error_response['error']['code'] === 'invalid_api_key') {
                    $error_msg = dgettext('CoursewareGPTBlock', 'Der OpenAI-API-Key ist fehlerhaft.');
                }
                if ($error_response['error']['code'] === 'model_not_found') {
                    $error_msg = dgettext('CoursewareGPTBlock', 'Das OpenAI-Model konnte nicht gefunden werden.');
                }
            } else {
                // Remove new lines
                $response = str_replace(PHP_EOL, ' ', $response);
                $error_msg = dgettext('CoursewareGPTBlock', "OpenAI-API-Fehler: $response.");
            }

            throw new \Trails_Exception(500, $error_msg);
        }

        return json_decode($response, true);
    }
}