<?php

class UpdateDefaultModel extends Migration {

    function description()
    {
        return 'Update default model gpt-3.5-turbo-0125 to gpt-4o-mini';
    }

    public function up()
    {
        $config = ConfigEntry::find('COURSEWARE_GPT_CHAT_MODEL');
        $config->value = 'gpt-4o-mini';
        $config->store();
    }

    public function down()
    {
        $config = ConfigEntry::find('COURSEWARE_GPT_CHAT_MODEL');
        $config->value = 'gpt-3.5-turbo-0125';
        $config->store();
    }
}
