<?php

class InitPlugin extends Migration {
    public function up()
    {
        $cfg = Config::get();

        $cfg->create('COURSEWARE_GPT_QUESTION_PROMPT',
            [
                'value' => 'You are a professor in an oral exam at a university teaching a course called "{{ title }}". A summary of the lecture this week is:

{{ summary }}

{{ if additional_instructions != "" }}
Take notice of the following important additional information:

{{ additional_instructions }}
{{ endif }}

The difficulty of the questions should be: {{ difficulty }}. Ask a question about the subject and answer it in a new line.
{{ if questions != "" }}
Only ask NEW questions that are different from the ones below!

{{ questions }}
{{ else }}
Example:

This is a question.

This is an answer.
{{ endif }}

Now the next question and answer pair:',
                'type' => 'i18n',
                'range' => 'global',
                'description' => 'Dieser Prompt wird für die Erzeugung von Fragen und Antworten an die OpenAI-API übermittelt. Der Prompt kann als Template definiert werden. Als Template-Parser wird die Stud.IP-interne Bibliothek "exTpl" verwendet.'
            ]
        );

        $cfg->create('COURSEWARE_GPT_FEEDBACK_PROMPT',
            [
                'value' => 'You are a professor in an oral exam at a university.

The question is: {{ question }}

I (the student) respond: {{ answer }}

Please give me an evaluation of my answer to the exam question, and give an explanation according to the sample solution: {{ solution }}{}',
                'type' => 'i18n',
                'range' => 'global',
                'description' => 'Dieser Prompt wird für die Erzeugung von Feedbacks zu einer Antwort an die OpenAI-API übermittelt. Der Prompt kann als Template definiert werden. Als Template-Parser wird die Stud.IP-interne Bibliothek "exTpl" verwendet.'
            ]
        );

        $cfg->create('COURSEWARE_GPT_API_KEY',
            [
                'type' => 'string',
                'range' => 'range',
                'description' => 'Der OpenAI-API-Key eines Kurses oder einer Person.'
            ]
        );
    }

    public function down()
    {
        $cfg = Config::get();
        $cfg->delete('COURSEWARE_GPT_QUESTION_PROMPT');
        $cfg->delete('COURSEWARE_GPT_FEEDBACK_PROMPT');
        $cfg->delete('COURSEWARE_GPT_API_KEY');
    }
}
