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
                                {{ _('Ich stimme zu, dass meine Antwort zur Auswertung an OpenAI weitergegeben werden darf. Bitte beachten Sie außerdem, dass Sie keine persönlichen Informationen oder sensiblen Daten wie Passwörter angeben.') }}</p>
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
                                {{ activeQuestion }}
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
                                {{ activeQuestion }}
                            </div>
                            <div v-show="answer" class="cw-gpt-section cw-gpt-section-answer">
                                <div class="cw-gpt-section-heading">
                                    {{ _('Deine Antwort') }}
                                </div>
                                {{ answer }}
                            </div>
                            <div v-show="feedback" class="cw-gpt-section cw-gpt-section-feedback">
                                <div class="cw-gpt-section-heading">
                                    <studip-icon shape="consultation" size="24"/>
                                    {{ _('Feedback') }}
                                </div>
                                {{ feedback }}
                            </div>
                            <div v-show="showSolution" class="cw-gpt-section cw-gpt-section-solution">
                                <div class="cw-gpt-section-heading">
                                    <studip-icon shape="doctoral-cap" size="24"/>
                                    {{ _('Musterlösung') }}
                                </div>
                                {{ solution }}
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
                        <label>
                            {{ _('OpenAI-API-Key') }}
                            <studip-tooltip-icon :text="_('Einen API-Key können Sie in den Accounteinstellungen der OpenAI-Webseite erstellen. Um diese Einstellungen sehen zu können, müssen Sie sich in Ihr OpenAI-Konto anmelden oder sich registrieren.')"/>
                            <input type="password" :placeholder="apiKeyPlaceholder" v-model="apiKey"/>
                        </label>
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
                                {{ _('Mit der Erstellung dieses Blockes bestätige ich, dass die Kursinhalte der aktuellen Seite keine geschützten personenbezogenen Daten enthalten und somit an OpenAI übermittelt werden dürfen.') }}</p>
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
            currentDifficulty: 'easy',
            currentUseBlockContents: false,
            apiKey: '',
            showConsent: true,
            showQuestion: false,
            showFeedback: false,
            showSolution: false,
            questions: [],  // previous questions
            activeQuestion: '',
            answer: '',
            feedback: '',
            solution: '',
            loading: false,
            translations: {},
        }
    },
    computed: {
        title() {
            return this.block?.attributes?.payload?.title;
        },
        hasApiKey() {
            return this.block?.attributes?.payload?.has_api_key;
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
        difficulty() {
            return this.block?.attributes?.payload?.difficulty;
        },
        useBlockContents() {
            return this.block?.attributes?.payload?.use_block_contents;
        },
        apiKeyPlaceholder() {
            if (!this.hasApiKey) {
                return '';
            }

            // Show asterisks when api key is saved on server
            return 'sk-**********************************';
        },
        language() {
            return this.$language.current;
        },
    },
    methods: {
        async loadTranslations() {
            this.translations = await $.getJSON(`${STUDIP.ABSOLUTE_URI_STUDIP}plugins_packages/virtUOS/CoursewareGPTBlockPlugin/locale/en/LC_MESSAGES/CoursewareGPTBlock.json`);
        },
        _(text) {
            if (this.language.includes("en") && text in this.translations) {
                return this.translations[text];
            }

            return text;
        },
        generateQuestion() {
            // Clear data
            this.answer = '';
            this.feedback = '';

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

                    this.activeQuestion = response.data.question;
                    this.solution = response.data.solution;

                    this.questions.push(this.activeQuestion + this.solution);
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
            formData.append('question', this.activeQuestion);
            formData.append('answer', this.answer);
            formData.append('solution', this.solution);

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
                    this.feedback = response.data.feedback;

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
        displaySolution() {
            this.showConsent = false;
            this.showQuestion = false;
            this.showFeedback = true;
            this.showSolution = true;
        },
        initCurrentData() {
            this.currentTitle = this.title;
            this.currentSummary = this.summary;
            this.currentAdditionalInstructions = this.additionalInstructions;
            this.currentDifficulty = this.difficulty;
            this.currentUseBlockContents = this.useBlockContents;
        },
        storeBlock() {
            if (!this.hasApiKey && this.apiKey === '') {
                this.$store.dispatch('companionWarning', {
                    info: this._('Bitte stelle einen OpenAI API Key bereit.')
                });
                return false;
            }

            if (!this.currentUseBlockContents && this.currentSummary === '') {
                this.$store.dispatch('companionWarning', {
                    info: this._('Bitte stelle den Inhalt der Veranstaltung bereit.')
                });
                return false;
            }

            let attributes= {
                payload: {
                    title: this.currentTitle,
                    api_key: this.apiKey,
                    summary: this.currentSummary,
                    additional_instructions: this.currentAdditionalInstructions,
                    difficulty: this.currentDifficulty,
                    use_block_contents: this.currentUseBlockContents
                }
            };

            return this.$store.dispatch("updateBlockInContainer", {
                attributes: attributes,
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
