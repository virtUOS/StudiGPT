<?php

class AddQuestionPool extends Migration
{
    function description()
    {
        return 'migrate existing block payload and update prompts for json output and feedback improvement';
    }

    public function up()
    {
        // Add question_mode to existing block payloads
        $gpt_blocks = \Courseware\Block::findBySQL("block_type = 'gpt' AND payload NOT LIKE '%\"question_mode\":%'");
        foreach ($gpt_blocks as $block) {
            $block_payload = json_decode($block->payload, true);

            if (!isset($block_payload['question_mode'])) {
                // Use english prompts in old blocks
                $block_payload['question_mode'] = 'random';
                $block->payload = json_encode($block_payload);
                $block->store();
            }
        }

        $cfg = Config::get();

        $question_prompt = $cfg->getValue('COURSEWARE_GPT_QUESTION_PROMPT');
        $question_prompt->setOriginal('Sie sind Professor/-in in einer mündlichen Prüfung an einer Universität und unterrichten einen Kurs mit dem Namen "{{ title }}". Eine Zusammenfassung der Lehrveranstaltung dieser Woche lautet:

{{ summary }}

{{ if additional_instructions != "" }}
Beachten Sie die folgenden wichtigen Zusatzinformationen:

{{ additional_instructions }}
{{ endif }}

Der Schwierigkeitsgrad der Fragen sollte sein: {{ difficulty }}. Stellen Sie {{ if number == 1 }}eine Frage mit Musterlösung{{ else }}{{ number }} Fragen mit Musterlösungen{{ endif }} zum Thema. Formatieren Sie die Ausgabe als JSON.
{{ if questions != "[]" }}
Stellen Sie nur NEUE Fragen, die sich von den unten stehenden unterscheiden!

{{ questions }}
{{ else }}
Beispiel:

[
    {
        "question": "Das ist eine Frage",
        "solution": "Das ist eine Antwort"
    }
]
{{ endif }}

Nun die {{ if number == 1 }}nächste Frage-Antwort{{ else }}nächsten {{ number }} Fragen-Antworten{{ endif }}:');

        $question_prompt->setLocalized('You are a professor in an oral exam at a university teaching a course called "{{ title }}". A summary of the lecture this week is:

{{ summary }}

{{ if additional_instructions != "" }}
Take notice of the following important additional information:

{{ additional_instructions }}
{{ endif }}

The difficulty of the questions should be: {{ difficulty }}. Ask {{ if number == 1 }}a question with a sample solution{{ else }}{{ number }} questions with sample solutions{{ endif }} about the subject. Format the output as JSON.
{{ if questions != "[]" }}
Only ask NEW questions that are different from the ones below!

{{ questions }}
{{ else }}
Example:

[
    {
        "question": "This is a question",
        "solution": "This is an answer"
    }
]
{{ endif }}

Now the {{ if number == 1 }}next question-answer{{ else }}next {{ number }} questions-answers{{ endif }}:', 'en_GB');
        $cfg->store('COURSEWARE_GPT_QUESTION_PROMPT', $question_prompt);


        $feedback_prompt = $cfg->getValue('COURSEWARE_GPT_FEEDBACK_PROMPT');
        $feedback_prompt->setOriginal('Sie sind Professor/-in in einer mündlichen Prüfung an einer Universität.

Die Frage lautet: {{ question }}

Ich (als Student/-in) antworte: {{ answer }}

Bitte geben Sie mir ein Feedback meiner Antwort auf die Prüfungsfrage und eine Erklärung gemäß der Musterlösung: {{ solution }}{}');
        $cfg->store('COURSEWARE_GPT_FEEDBACK_PROMPT', $feedback_prompt);

    }

    public function down()
    {
        $cfg = Config::get();

        $question_prompt = $cfg->getValue('COURSEWARE_GPT_QUESTION_PROMPT');
        $question_prompt->setOriginal('Sie sind Professor/-in in einer mündlichen Prüfung an einer Universität und unterrichten einen Kurs mit dem Namen "{{ title }}". Eine Zusammenfassung der Lehrveranstaltung dieser Woche lautet:

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

Nun das nächste Frage-Antwort-Paar:');

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

Now the next question and answer pair:', 'en_GB');
        $cfg->store('COURSEWARE_GPT_QUESTION_PROMPT', $question_prompt);


        $feedback_prompt = $cfg->getValue('COURSEWARE_GPT_FEEDBACK_PROMPT');
        $feedback_prompt->setOriginal('Sie sind Professor/-in in einer mündlichen Prüfung an einer Universität.

Die Frage lautet: {{ question }}

Ich (als Student/-in) antworte: {{ answer }}

Bitte geben Sie mir eine Bewertung meiner Antwort auf die Prüfungsfrage und eine Erklärung gemäß der Musterlösung: {{ solution }}{}');
        $cfg->store('COURSEWARE_GPT_FEEDBACK_PROMPT', $feedback_prompt);
    }
}