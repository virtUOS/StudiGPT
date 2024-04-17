<?php

class FixPayloadLanguage extends Migration
{

    function description()
    {
        return 'Add missing language-key-value pair in payload';
    }

    public function up()
    {
        $gpt_blocks = \Courseware\Block::findBySQL("block_type = 'gpt' AND payload NOT LIKE '%\"language\":%'");
        foreach ($gpt_blocks as $block) {
            $block_payload = json_decode($block->payload, true);

            if (!isset($block_payload['language'])) {
                // Use english prompts in old blocks
                $block_payload['language'] = 'en_GB';
                $block->payload = json_encode($block_payload);
                $block->store();
            }
        }
    }

    public function down() {}
}
