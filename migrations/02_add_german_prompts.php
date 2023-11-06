<?php

class AddGermanPrompts extends Migration
{
    function description()
    {
        return 'add german prompts to config COURSEWARE_GPT_QUESTION_PROMPT and COURSEWARE_GPT_FEEDBACK_PROMPT';
    }

    function up()
    {
        $cfg = Config::get();
        // COURSEWARE_GPT_QUESTION_PROMPT
        // Delete old config
        $cfg->delete('COURSEWARE_GPT_QUESTION_PROMPT');

        // Recreate config with german prompts as default
        $cfg->create('COURSEWARE_GPT_QUESTION_PROMPT',
            [
                'value' => 'Sie sind Professor in einer mündlichen Prüfung an einer Universität und unterrichten einen Kurs mit dem Namen "{{ title }}". Eine Zusammenfassung der Lehrveranstaltung dieser Woche lautet:

{{ summary }}

{{ if additional_instructions != "" }}
Beachten Sie die folgenden wichtigen Zusatzinformationen:

{{ additional_instructions }}
{{ endif }}

Der Schwierigkeitsgrad der Fragen sollte sein: {{ difficulty }}. Stellen Sie eine Frage zum Thema und beantworten Sie sie in einer neuen Zeile.
{{ if questions != "" }}
Stellen Sie nur NEUE Fragen, die sich von den unten stehenden unterscheiden!

{{ questions }}
{{ else }}
Beispiel:

Das ist eine Frage.

Das ist eine Antwort.
{{ endif }}

Nun das nächste Frage-Antwort-Paar:',
                'type' => 'i18n',
                'range' => 'global',
                'description' => 'Dieser Prompt wird für die Erzeugung von Fragen und Antworten an die OpenAI-API übermittelt. Der Prompt kann als Template definiert werden. Als Template-Parser wird die Stud.IP-interne Bibliothek "exTpl" verwendet.'
            ]
        );

        // Add english translation
        $question_prompt = $cfg->getValue('COURSEWARE_GPT_QUESTION_PROMPT');
        $question_prompt->setLocalized('You are a professor in an oral exam at a university teaching a course called "{{ title }}". A summary of the lecture this week is:

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
            'en_GB');
        $cfg->store('COURSEWARE_GPT_QUESTION_PROMPT', $question_prompt);

        // COURSEWARE_GPT_FEEDBACK_PROMPT
        // Delete old config
        $cfg->delete('COURSEWARE_GPT_FEEDBACK_PROMPT');

        // Recreate config with german prompts as default
        $cfg->create('COURSEWARE_GPT_FEEDBACK_PROMPT',
            [
                'value' => 'Sie sind ein Professor in einer mündlichen Prüfung an einer Universität.

Die Frage lautet: {{ question }}

Ich (der Student) antworte: {{ answer }}

Bitte geben Sie mir eine Bewertung meiner Antwort auf die Prüfungsfrage und eine Erklärung gemäß der Musterlösung: {{ solution }}{}',
                'type' => 'i18n',
                'range' => 'global',
                'description' => 'Dieser Prompt wird für die Erzeugung von Feedbacks zu einer Antwort an die OpenAI-API übermittelt. Der Prompt kann als Template definiert werden. Als Template-Parser wird die Stud.IP-interne Bibliothek "exTpl" verwendet.'
            ]
        );

        // Add english translation
        $feedback_prompt = $cfg->getValue('COURSEWARE_GPT_FEEDBACK_PROMPT');
        $feedback_prompt->setLocalized('You are a professor in an oral exam at a university.

The question is: {{ question }}

I (the student) respond: {{ answer }}

Please give me an evaluation of my answer to the exam question, and give an explanation according to the sample solution: {{ solution }}{}',
            'en_GB');
        $cfg->store('COURSEWARE_GPT_FEEDBACK_PROMPT', $feedback_prompt);

    }
}
