# KI-Quiz

Courseware-Block zur Generierung von Fragen und Feedback zu Antworten basierend auf dem Inhalt einer Courseware-Seite. Derzeit wird das große Sprachmodell GPT-3 von openAI für die Generierung der Fragen und die Auswertung verwendet.

## Details
Das [digitale Lehre-Portal der Universität Osnabrück](https://digitale-lehre.uni-osnabrueck.de/2024/01/19/studigpt-das-innovative-ki-basierte-stud-ip-plugin/) beinhaltet unter anderem Informationen zu der Nutzung und den Funktionen des Plugins.

## Update von StudiGPT
Um das alte Plugin "StudiGPT" zu aktualisieren, muss zuerst das neue Plugin "KI-Quiz" installiert werden. Anschließend sollte das Schema des alten Plugins den Wert 0 besitzen. In diesem Fall kann das alte Plugin deinstalliert werden, sodass keine vorhandenen Daten verloren gehen.

## Konfiguration

Die Konfigurationen befinden sich auf der Adminoberfläche unter `Admin->System->Konfiguration->Globale Konfiguration->KI-Quiz`

Die folgenden Konfigurationen sind verfügbar:

| Name                           | Description                                                                                                                                                                                                                                                                |
|--------------------------------|----------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------|
| COURSEWARE_GPT_API_KEY         | Globaler API-Key, der von allen im Courseware-Block verwendet werden kann. Ein benutzerdefinierter API-Key kann im Block immer konfiguriert werden. Wenn kein globaler API-Key hinterlegt ist, kann das Plugin nur mit einem benutzerdefinierten API-Key verwendet werden. |
| COURSEWARE_GPT_CHAT_MODEL      | Chat Model von OpenAI, welches immer mit dem globalen API-Key verwendet wird. Das Model muss mit der `Chat Completions` API von OpenAI kompatibel sein. Bei einem benutzerdefinierten API-Key kann ein abweichendes Model im Block eingestellt werden.                     |
| COURSEWARE_GPT_QUESTION_PROMPT | Prompt, der an die OpenAI-API gesendet wird, um ein Feedback auf eine Antwort zu generieren. Der Prompt unterstützt Stud.IP internes Templating, siehe Abschnitt Templating.                                                                                               |
| COURSEWARE_GPT_FEEDBACK_PROMPT | Prompt, der an die OpenAI-API gesendet wird, um eine Frage und eine Musterlösung zu generieren. Der Prompt unterstützt Stud.IP internes Templating, siehe Abschnitt Templating.                                                                                            |

## Templating

Die Bibliothek `exTpl` wird für das Parsen der Templates verwendet.

### Unterstützte Platzhalter

Dieser Abschnitt listet alle verfügbaren Platzhalter für die einzelnen Prompts auf.

Platzhalter in `COURSEWARE_GPT_QUESTION_PROMPT`:

| Name                      | Description                                                                               |
|---------------------------|-------------------------------------------------------------------------------------------|
| `title`                   | Der Kurstitel, wenn ein Kurskontext vorhanden ist. Ansonsten der Titel des Lernmaterials. |
| `summary`                 | Die Zusammenfassung des Blocks                                                            |
| `difficulty`              | Die ausgewählte Schwierigkeit                                                             |
| `questions`               | Die zuvor generierten Fragen                                                              |
| `additional_instructions` | Die zusätzlichen Anweisungen des Blockerstellers                                          |

Platzhalter in `COURSEWARE_GPT_FEEDBACK_PROMPT`:

| Name       | Description              |
|------------|--------------------------|
| `question` | Die beantwortete Frage   |
| `answer`   | Die Antwort des Nutzers  |
| `solution` | Die Musterlösung von GPT |

### Beispiele
Mehr Beispiele können im [Stud.IP-Code](https://gitlab.studip.de/studip/studip) unter `vendor/exTpl/template_test.php` gefunden werden.

#### Einfache Platzhalter
Template: `The difficulty of the questions should be: {{ difficulty }}`

Der Platzhalter `difficulty` wird durch die gewählte Schwierigkeit ersetzt.

#### Bedingungen
Template: `{{ if questions != "" }} Questions are set {{ else }} No questions are set {{ endif }}`

## Mitwirkende
Dieses Plugin basiert auf Ideen aus einer prototypisch entwickelten Webanwendung von den Studenten [Maximilian Kalcher (mklacher@uos.de)](mailto:mklacher@uos.de) und [Konstantin Strömel (kstroemel@uos.de)](mailto:kstroemel@uos.de). Sie beteiligten sich mit ihrer Expertise bei der Anwendung von Large Language Models, um die Generierung von Fragen, Musterlösungen und Feedback durch Prompt Engineering zu ermöglichen. Außerdem stellten sie Anforderungen an die Bedienoberfläche und die Funktionalitäten und wirkten so an der Umsetzung dieses Projektes mit. Die beiden können gerne bei allgemeinen Fragen zur Anwendung von generativer KI für Lernanwendungen kontaktiert werden, sowie spezifischen Fragen zum Einsatz von LLMs und Prompt Engineering. Außerdem sind sie offen für Vorschläge zu neuen Features oder Kollaborationen, um diese gemeinsam umzusetzen.

Die technische Entwicklung wird vom Zentrum virtUOS der Universität Osnabrück ausgeführt. Falls Fragen zur Nutzung des Plugins in Courseware oder zu möglichen Erweiterungen auftreten, können [Dennis Benz (debenz@uos.de)](mailto:debenz@uos.de) und [Lars Kiesow (lkiesow@uos.de)](mailto:lkiesow@uos.de) per Mail erreicht werden. Bei technischen Fragen oder Problemen können [Issues](https://github.com/virtUOS/KI-Quiz/issues) erstellt werden.
