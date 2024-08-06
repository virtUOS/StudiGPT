<?php

/**
 * Sends a prompt to the configured LLM API
 *
 * @param $prompt string prompt
 * @param $block_payload array json decoded payload of passed block
 *
 * @return mixed|null json decoded response
 *
 * @throws Trails_Exception when the request fails
 */
function sendPrompt(string $prompt, \Courseware\Block $block, array $block_payload) {
    // TODO: Add more LLM APIs / Clients here
    $client = \CoursewareGPTBlock\OpenaiClient::getInstance();

    $range_id = $block->container->getStructuralElement()->range_id;
    $api_key_origin = $block_payload['api_key_origin'];

    // Get endpoint
    $endpoint = \CoursewareGPTBlock\GPTClient::getGlobalApiEndpoint();
    if ($api_key_origin === 'custom') {
        // Use custom endpoint if own api key and endpoint not empty
        if (!empty($block_payload['custom_endpoint'])) {
            $endpoint = $block_payload['custom_endpoint'];
        }
    }

    // Get chat model
    $chat_model = \CoursewareGPTBlock\GPTClient::getGlobalChatModel();
    if ($api_key_origin === 'custom') {
        // Use custom chat model if own api key and model not empty
        if (!empty($block_payload['custom_chat_model'])) {
            $chat_model = $block_payload['custom_chat_model'];
        }
    }

    return $client->request($prompt, $api_key_origin, $range_id, $endpoint, $chat_model);
}

/**
 * Strips html tags from the passed summary and formats the summary.
 *
 * @param string $summary
 * @return string Striped and formatted summary
 */
function formatSummary(string $summary): string {
    // Add new lines after tags: <br>, </p>, </h*>, </div>, </li>, </section>, </article>, </blockquote>, </details>, </summary>
    $summary = preg_replace([
        '/<br.*?>/i',
        '/<\\/p.*?>/i',
        '/<\\/h[1-6].*?>/i',
        '/<\\/div.*?>/i',
        '/<\\/li.*?>/i',
        '/<\\/section.*?>/i',
        '/<\\/article.*?>/i',
        '/<\\/blockquote.*?>/i',
        '/<\\/details.*?>/i',
        '/<\\/summary.*?>/i',
    ], "$0\n" , $summary);

    // Remove html tags
    $summary = strip_tags($summary);

    return trim($summary);
}

/**
 * Creates a summary from all text blocks of a structural element
 *
 * Supported block types: 'headline', 'document', 'dialog-cards', 'key-point', 'code', 'typewriter', 'text', 'biography-goals'
 *
 * @param \Courseware\Block $site_block block of a courseware site
 * @return string summary of a courseware site
 */
function getCoursewareSummary(\Courseware\Block $site_block) {
    $structural_element = $site_block->container->getStructuralElement();

    $summary = '';

    // Add site title
    $summary .= $structural_element->title . "\n\n";

    // Iterate over all containers
    foreach ($structural_element->containers as $container) {
        foreach ($container->blocks as $block) {
            $payload = json_decode($block->payload, true);
            $block_summary = '';
            switch ($block->getBlockType()) {
                case \Courseware\BlockTypes\Text::getType():
                    $block_summary .= $payload['text'] ?? '';
                    break;
                case \Courseware\BlockTypes\Code::getType():
                    if (isset($payload['content'])) {
                        $lang = $payload['lang'] ?? "";
                        $block_summary .= "{$lang} code:\n";
                        $block_summary .= $payload['content'];
                    }
                    break;
                case \Courseware\BlockTypes\Headline::getType():
                    $block_summary .= $payload['title'] . "\n" . $payload['subtitle'] ?? '';
                    break;
                case \Courseware\BlockTypes\KeyPoint::getType():
                    $block_summary .= $payload['text'] ?? '';
                    break;
                case \Courseware\BlockTypes\DialogCards::getType():
                    if (isset($payload['cards']) && is_array($payload['cards'])) {
                        foreach ($payload['cards'] as $card) {
                            $block_summary .= $card['front_text'] . "\n" . $card['back_text'] ?? '';
                        }
                    }
                    break;
                case \Courseware\BlockTypes\Typewriter::getType():
                    $block_summary .= $payload['text'] ?? '';
                    break;
                //case \Courseware\BlockTypes\BiographyGoals::getType():
                //    $block_summary .= $payload['type'] . "\n" . $payload['description'] ?? '';
                //    break;
                case \Courseware\BlockTypes\Document::getType():
                    // TODO: Process pdf file
                    break;
            }

            // Remove whitespace and html tags
            $block_summary = formatSummary($block_summary);
            if (!empty($block_summary)) {
                $summary .= $block_summary . "\n\n";
            }
        }
    }

    return trim($summary);
}
