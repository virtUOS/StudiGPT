const CoursewareGPTBlock= {
    template:
        `
            <div class="cw-block cw-block-gpt">
            <component
                :is="coursewarePluginComponents.CoursewareDefaultBlock"
                :block="block"
                :canEdit="canEdit"
                :isTeacher="isTeacher"
                :preview="false"
                :defaultGrade="false"
                @showEdit="initCurrentData"
                @storeEdit="storeBlock"
                @closeEdit="initCurrentData"
            >
                <template #content>
                    <div v-if="currentTitle !== ''" class="cw-block-title">{{ currentTitle }}</div>
                    <div v-show="!loading">
                        <div v-if="showConsent" class="cw-gpt-consent">
                            <p>
                                {{ _('Ich stimme zu, dass meine Antwort zur Auswertung an OpenAI weitergegeben werden darf und für Evaluationszwecke anonym gespeichert wird. Ich stelle sicher, dass ich keine persönlichen Informationen oder sensiblen Daten wie Passwörter angebe. Die generierten Fragen und Feedback sowie meine Antworten werden vollständig anonym in Stud.IP gespeichert, um die Usability dieser Funktion und die Qualität der generierten Fragen und Feedback evaluieren zu können. Überprüfen Sie immer die Korrektheit der generierten Texte, da diese von der KI erzeugte Halluzinationen enthalten können.') }}
                            </p>
                            <div class="cw-gpt-buttons">
                                <button @click="generateQuestion" class="button accept">
                                    {{ _('Zustimmen und Frage generieren') }}
                                </button>
                            </div>
                        </div>
                        <div v-if="showQuestion" class="cw-gpt-question">
                            <div class="cw-gpt-section cw-gpt-section-question">
                                <div class="cw-gpt-section-heading">
                                    <studip-icon shape="question" size="24"/>
                                </div>
                                {{ activeQuestion.question }}
                                <div class="cw-gpt-user-feedback">
                                    <div @click="submitUserFeedback('question', 1)" 
                                         class="cw-gpt-user-feedback-like"
                                         :class="{ selected: userQuestionFeedback?.value === 1 }"
                                    />
                                    <div @click="submitUserFeedback('question', 0)"
                                         class="cw-gpt-user-feedback-dislike"
                                         :class="{ selected: userQuestionFeedback?.value === 0 }"
                                    />
                                </div>
                            </div>
                            <form class="default" @submit.prevent="">
                                <label class="cw-gpt-section cw-gpt-answer-input undecorated">
                                    <span class="cw-gpt-section-heading">
                                        {{ _('Deine Antwort') }}
                                    </span>
                                    <textarea v-model="answer"/>
                                </label>
                                <div class="cw-gpt-buttons">
                                    <button :disabled="!answer" @click="generateFeedback" class="button">
                                        {{ _('Feedback generieren') }}
                                    </button>
                                    <button @click="displaySolution" class="button">
                                        {{ _('Musterlösung anzeigen') }}
                                    </button>
                                </div>
                            </form>
                        </div>
                        <div v-if="showFeedback" class="cw-gpt-feedback">
                            <div class="cw-gpt-section cw-gpt-section-question">
                                <div class="cw-gpt-section-heading">
                                    <studip-icon shape="question" size="24"/>
                                </div>
                                {{ activeQuestion.question }}
                                <div class="cw-gpt-user-feedback">
                                    <div @click="submitUserFeedback('question', 1)"
                                         class="cw-gpt-user-feedback-like"
                                         :class="{ selected: userQuestionFeedback?.value === 1 }"
                                    />
                                    <div @click="submitUserFeedback('question', 0)"
                                         class="cw-gpt-user-feedback-dislike"
                                         :class="{ selected: userQuestionFeedback?.value === 0 }"
                                    />
                                </div>
                            </div>
                            <div v-show="answer" class="cw-gpt-section cw-gpt-section-answer">
                                <div class="cw-gpt-section-heading">
                                    {{ _('Deine Antwort') }}
                                </div>
                                {{ answer }}
                            </div>
                            <div v-show="feedback.content" class="cw-gpt-section cw-gpt-section-feedback">
                                <div class="cw-gpt-section-heading">
                                    <studip-icon shape="consultation" size="24"/>
                                    {{ _('Feedback') }}
                                </div>
                                {{ feedback.content }}
                                <div class="cw-gpt-user-feedback">
                                    <div @click="submitUserFeedback('feedback', 1)"
                                         class="cw-gpt-user-feedback-like"
                                         :class="{ selected: userFeedbackFeedback?.value === 1 }"
                                    />
                                    <div @click="submitUserFeedback('feedback', 0)"
                                         class="cw-gpt-user-feedback-dislike"
                                         :class="{ selected: userFeedbackFeedback?.value === 0 }"
                                    />
                                </div>
                            </div>
                            <div v-show="showSolution" class="cw-gpt-section cw-gpt-section-solution">
                                <div class="cw-gpt-section-heading">
                                    <studip-icon shape="doctoral-cap" size="24"/>
                                    {{ _('Musterlösung') }}
                                </div>
                                {{ activeQuestion.solution }}
                            </div>
                            <div class="cw-gpt-buttons">
                                <select v-model="currentDifficulty">
                                    <option value="easy">{{ _('Einfach') }}</option>
                                    <option value="hard">{{ _('Schwierig') }}</option>
                                </select>
                                <button @click="generateQuestion" class="button">
                                    {{ _('Neue Frage generieren') }}
                                </button>
                                <button v-show="!showSolution" @click="displaySolution" class="button">
                                    {{ _('Musterlösung anzeigen') }}
                                </button>
                            </div>
                        </div>
                    </div>
                    <div v-show="loading" class="cw-gpt-loading-indicator"></div>
                </template>
                <template v-if="canEdit" #edit>
                    <form class="default cw-gpt-edit-form" @submit.prevent="">
                        <label>
                            {{ _('Titel') }}
                            <input type="text" v-model="currentTitle"/>
                        </label>
                        <label v-if="hasGlobalApiKey">
                            {{ _('OpenAI-API-Key') }}
                            <studip-tooltip-icon :text="_('Sie können wählen, ob Sie den zentral hinterlegten OpenAI-API-Key Ihres Standorts oder Ihren eigenen Key verwenden möchten.')"/>
                            <select v-model="currentApiKeyOrigin">
                                <option value="global">{{ _('Zentraler API-Key') }}</option>
                                <option value="custom">{{ _('Eigener API-Key') }}</option>
                            </select>
                        </label>
                        <div v-show="!globalApiKeySelected">
                            <label>
                                {{ _('Eigener OpenAI-API-Key') }}
                                <studip-tooltip-icon :text="_('Geben Sie Ihren eigenen API-Key an. Einen API-Key können Sie in den Accounteinstellungen der OpenAI-Webseite erstellen. Um diese Einstellungen sehen zu können, müssen Sie sich in Ihr OpenAI-Konto anmelden oder sich registrieren.')"/>
                                <input type="password" :placeholder="customApiKeyPlaceholder" v-model="customApiKey"/>
                            </label>
                            <label>
                                {{ _('OpenAI-Chat-Model') }}
                                <studip-tooltip-icon :text="_('Sie können zu Ihrem API-Key das zu verwendende OpenAI-Chat-Model angeben. Das Model muss mit der Chat Completions API von OpenAI kompatibel sein. Wenn Sie kein Model angeben, wird das in Stud.IP zentral konfigurierte Model verwendet.')"/>
                                <input type="text" :placeholder="globalChatModel" v-model="currentCustomChatModel"/>
                            </label>
                        </div>
                        <label>
                            <input type="checkbox" v-model="currentUseBlockContents"/>
                            {{ _('Inhalt aus Textblöcken mitsenden') }}
                        </label>
                        <label v-show="currentUseBlockContents">
                            {{ _('Text aus den umgebenden Blöcken') }}
                            <textarea v-model="textBlockSummary" maxlength="16000" disabled/>
                        </label>
                        <label v-show="!currentUseBlockContents">
                            {{ _('Inhalt der Veranstaltung') }}
                            <textarea v-model="currentSummary" maxlength="16000"/>
                        </label>
                        <label>
                            {{ _('Zusätzliche Anweisungen') }}
                            <studip-tooltip-icon :text="_('Hier können Sie dem Model zusätzliche Informationen mitgeben, wie beispielsweise einen Fokus auf einen bestimmten Teil des Lerninhalts oder eine Anweisung.')"/>
                            <textarea v-model="currentAdditionalInstructions" :placeholder="_('Example: Do not ask a question about neural networks')"/>
                        </label>
                        <label>
                            {{ _('Sprache') }}
                            <studip-tooltip-icon :text="_('Hiermit geben Sie an, in welcher Sprache die Fragen und das Feedback generiert werden sollen.')"/>
                            <select v-model="currentLanguage">
                                <option value="de_DE">{{ _('Deutsch') }}</option>
                                <option value="en_GB">{{ _('Englisch') }}</option>
                            </select>
                        </label>
                        <label>
                            {{ _('Schwierigkeit') }}
                            <select v-model="currentDifficulty">
                                <option value="easy">{{ _('Einfach') }}</option>
                                <option value="hard">{{ _('Schwierig') }}</option>
                            </select>
                        </label>
                        <div class="cw-gpt-privacy">
                            <header>
                                <studip-icon shape="info" size="16"/>
                                {{ _('Datenschutz') }}
                            </header>
                            <p>
                                {{ _('Mit der Erstellung dieses Blockes bestätige ich, dass die Kursinhalte der aktuellen Seite keine geschützten personenbezogenen Daten enthalten und somit an OpenAI übermittelt werden dürfen. Während der Nutzung werden die generierten Fragen und Musterlösungen mit den angegebenen Einstellungen in Stud.IP abgespeichert, um die Usability dieses Blocks und die Qualität der generierten Fragen und Feedback evaluieren zu können. Zu diesen Einstellungen zählen der Lehrinhalt, die zusätzlichen Anweisungen, die Sprache und die Schwierigkeit.') }}
                            </p>
                        </div>
                    </form>
                </template>
                <template #info>
                    <p>{{ _('Informationen zum StudiGPT-Block') }}</p>
                </template>
            </component>
            </div>`,

    name: 'courseware-gpt-block',
    props: {
        block: Object,
        canEdit: Boolean,
        isTeacher: Boolean
    },
    data() {
        return {
            currentTitle: '',
            currentSummary: '',
            currentAdditionalInstructions: '',
            currentLanguage: 'de_DE',
            currentDifficulty: 'easy',
            currentUseBlockContents: false,
            currentApiKeyOrigin: 'global',
            customApiKey: '',
            currentCustomChatModel: '',
            showConsent: true,
            showQuestion: false,
            showFeedback: false,
            showSolution: false,
            questions: [],  // previous question ids
            activeQuestion: {
                id: '',
                question: '',
                solution: '',
            },
            answer: '',
            feedback: {
                id: '',
                content: '',
            },
            userQuestionFeedback: null,
            userFeedbackFeedback: null,
            loading: false,
            translations: {},
        }
    },
    computed: {
        title() {
            return this.block?.attributes?.payload?.title;
        },
        apiKeyOrigin() {
            return this.block?.attributes?.payload?.api_key_origin;
        },
        hasGlobalApiKey() {
            return this.block?.attributes?.payload?.has_global_api_key;
        },
        hasCustomApiKey() {
            return this.block?.attributes?.payload?.has_custom_api_key;
        },
        globalChatModel() {
            return this.block?.attributes?.payload?.global_chat_model;
        },
        customChatModel() {
            return this.block?.attributes?.payload?.custom_chat_model;
        },
        summary() {
            return this.block?.attributes?.payload?.summary;
        },
        additionalInstructions() {
            return this.block?.attributes?.payload?.additional_instructions;
        },
        textBlockSummary() {
            return this.block?.attributes?.payload?.text_block_summary;
        },
        language() {
            return this.block?.attributes?.payload?.language;
        },
        difficulty() {
            return this.block?.attributes?.payload?.difficulty;
        },
        useBlockContents() {
            return this.block?.attributes?.payload?.use_block_contents;
        },
        customApiKeyPlaceholder() {
            if (!this.hasCustomApiKey) {
                return '';
            }

            // Show asterisks when api key is saved on server
            return 'sk-**********************************';
        },
        globalLanguage() {
            return this.$language.current;
        },
        globalApiKeySelected() {
            return this.hasGlobalApiKey && this.currentApiKeyOrigin === 'global';
        },
    },
    methods: {
        async loadTranslations() {
            this.translations = await $.getJSON(`${STUDIP.ABSOLUTE_URI_STUDIP}plugins_packages/virtUOS/StudiGPT/locale/en/LC_MESSAGES/CoursewareGPTBlock.json`);
        },
        _(text) {
            if (this.globalLanguage.includes("en") && text in this.translations) {
                return this.translations[text];
            }

            return text;
        },
        generateQuestion() {
            // Clear data
            this.answer = '';
            this.feedback.id = '';
            this.feedback.content = '';
            this.userQuestionFeedback = null;
            this.userFeedbackFeedback = null;

            const formData = new FormData();
            formData.append('questions', JSON.stringify(this.questions));
            formData.append('difficulty', this.currentDifficulty);

            // OpenAi Question Request
            this.loading = true;
            this.$store.getters.httpClient
                .post(
                    COURSEWARE_GPT_BLOCK_BASE_URL + '/api/question/' + this.block.id, formData, {
                        headers: {
                            'Content-Type': 'multipart/form-data'
                        }
                    })
                .then(response => {
                    this.loading = false;

                    this.activeQuestion.id = response.data.id;
                    this.activeQuestion.question = response.data.question;
                    this.activeQuestion.solution = response.data.solution;

                    this.questions.push(this.activeQuestion.id);
                    // Remove first elements until 5 questions are left
                    while (this.questions.length > 5) {
                        this.questions.shift();
                    }

                    this.showConsent = false;
                    this.showQuestion = true;
                    this.showFeedback = false;
                    this.showSolution = false;
                })
                .catch(error => {
                    this.loading = false;
                    this.handleApiError(error, this._('Frage konnte nicht generiert werden.'));
                });
        },
        handleApiError(error, defaultMessage) {
            let msg = defaultMessage;
            if (error.response) {
                msg = error.response.statusText;
            }

            this.$store.dispatch('companionError', {
                info: msg
            });
        },
        generateFeedback() {
            const formData = new FormData();
            formData.append('question_id', this.activeQuestion.id);
            formData.append('answer', this.answer);

            // OpenAI Feedback Request
            this.loading = true;
            this.$store.getters.httpClient
                .post(COURSEWARE_GPT_BLOCK_BASE_URL + '/api/feedback/' + this.block.id, formData, {
                    headers: {
                        'Content-Type': 'multipart/form-data'
                    }
                })
                .then(response => {
                    this.loading = false;

                    // this.feedback = 'Das ist richtig!';
                    this.feedback.id = response.data.id;
                    this.feedback.content = response.data.feedback;

                    // this.showConsent = false;
                    this.showQuestion = false;
                    this.showFeedback = true;
                    // this.showSolution = false;
                })
                .catch(error => {
                    this.loading = false;
                    this.handleApiError(error, this._('Feedback konnte nicht erzeugt werden.'));
                });
        },
        submitUserFeedback(range_type, value) {
            const formData = new FormData();

            let user_feedback_id = null;
            let range_id = null;

            if ( range_type === 'question') {
                user_feedback_id = this.userQuestionFeedback?.id;
                range_id =  this.activeQuestion.id;
            } else {
                user_feedback_id = this.userFeedbackFeedback?.id;
                range_id =  this.feedback.id;
            }

            if (user_feedback_id) {
                // Allows to update existing user feedback
                formData.append('user_feedback_id', user_feedback_id);
            }

            formData.append('range_id', range_id);
            formData.append('range_type', range_type);
            formData.append('feedback_value', value);

            this.$store.getters.httpClient
                .post(COURSEWARE_GPT_BLOCK_BASE_URL + '/api/user_feedback/' + this.block.id, formData, {
                    headers: {
                        'Content-Type': 'multipart/form-data'
                    }
                })
                .then(response => {
                    // Store user feedback
                    if (response.data?.range_type === 'question') {
                        this.userQuestionFeedback = response.data;
                    } else if (response.data?.range_type === 'feedback') {
                        this.userFeedbackFeedback = response.data;
                    }
                })
                .catch(error => {
                    this.handleApiError(error, this._('Feedback konnte nicht gespeichert werden.'));
                });
        },
        displaySolution() {
            this.showConsent = false;
            this.showQuestion = false;
            this.showFeedback = true;
            this.showSolution = true;
        },
        initCurrentData() {
            this.currentTitle = this.title;
            this.currentApiKeyOrigin = this.hasGlobalApiKey ? this.apiKeyOrigin : 'custom';
            this.currentCustomChatModel = this.customChatModel;
            this.currentSummary = this.summary;
            this.currentAdditionalInstructions = this.additionalInstructions;
            this.currentLanguage = this.language;
            this.currentDifficulty = this.difficulty;
            this.currentUseBlockContents = this.useBlockContents;
        },
        storeBlock() {
            if (!this.globalApiKeySelected && !this.hasCustomApiKey && this.customApiKey === '') {
                this.$store.dispatch('companionWarning', {
                    info: this._('Bitte stellen Sie Ihren OpenAI-API-Key bereit.')
                });
                return false;
            }

            if (!this.currentUseBlockContents && this.currentSummary === '') {
                this.$store.dispatch('companionWarning', {
                    info: this._('Bitte stellen Sie den Inhalt der Veranstaltung bereit.')
                });
                return false;
            }

            let payload = {
                title: this.currentTitle,
                api_key_origin: this.currentApiKeyOrigin,
                summary: this.currentSummary,
                additional_instructions: this.currentAdditionalInstructions,
                language: this.currentLanguage,
                difficulty: this.currentDifficulty,
                use_block_contents: this.currentUseBlockContents
            };

            // Send custom api key and model if global api key is not used
            if (!this.globalApiKeySelected) {
                payload.custom_api_key = this.customApiKey;
                payload.custom_chat_model = this.currentCustomChatModel;
            }

            return this.$store.dispatch("updateBlockInContainer", {
                attributes: {
                    payload: payload,
                },
                blockId: this.block.id,
                containerId: this.block.relationships.container.data.id,
            });
        },
    },
    async mounted() {
        this.initCurrentData();
        await this.loadTranslations();
    },
    inject: ['coursewarePluginComponents']
}

window.STUDIP.eventBus.on("courseware:init-plugin-manager", (pluginManager) => {
    pluginManager.addBlock("courseware-gpt-block", CoursewareGPTBlock);
});
