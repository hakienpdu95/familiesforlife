/**
 * SurveyBehaviorTracker — Module 110 (T3)
 *
 * Tracks user interaction events during survey completion and flushes
 * them to POST /api/v1/surveys/{slug}/behavior after the survey is submitted.
 *
 * Usage (Option A — flush after submit):
 *   const tracker = new SurveyBehaviorTracker({ slug, bearerToken, apiBase });
 *   tracker.attach(formEl);           // attach listeners to all question elements
 *   const responseId = await submitSurvey();
 *   await tracker.flush(responseId); // send all buffered events
 *
 * Event types tracked:
 *   question_focus   — user focused on a question input
 *   question_blur    — user left a question input
 *   answer_changed   — user changed an answer
 *   answer_cleared   — user cleared/reset an answer
 *   section_entered  — user scrolled into a new section
 *   time_spent       — seconds spent on a question (emitted on blur)
 */
export class SurveyBehaviorTracker {
    /**
     * @param {object} opts
     * @param {string} opts.slug         Survey slug
     * @param {string} opts.bearerToken  Survey API token
     * @param {string} [opts.apiBase]    Base URL, default '/api'
     */
    constructor({ slug, bearerToken, apiBase = '/api' }) {
        this.slug        = slug;
        this.token       = bearerToken;
        this.apiBase     = apiBase;
        this.buffer      = [];
        this._focusTimes = {};  // question_code → Date.now() on focus
        this._listeners  = [];  // [{el, type, fn}] for cleanup
    }

    // ── Public API ──────────────────────────────────────────────────────────

    /**
     * Attach event listeners to all question elements inside `containerEl`.
     * Elements should have a [data-question-code] attribute.
     *
     * @param {HTMLElement} containerEl
     */
    attach(containerEl) {
        if (! containerEl) return;

        // All focusable inputs/selects/textareas with a question code
        const inputs = containerEl.querySelectorAll(
            '[data-question-code] input, [data-question-code] select, [data-question-code] textarea'
        );

        inputs.forEach(el => {
            const code = el.closest('[data-question-code]')?.dataset?.questionCode ?? null;

            this._on(el, 'focus', () => this._onFocus(el, code));
            this._on(el, 'blur',  () => this._onBlur(el, code));
            this._on(el, 'change', () => this._onChange(el, code));
            this._on(el, 'input',  () => this._onChange(el, code));
        });

        // Section visibility via IntersectionObserver
        const sections = containerEl.querySelectorAll('[data-section-code]');
        if (sections.length && 'IntersectionObserver' in window) {
            const obs = new IntersectionObserver(entries => {
                entries.forEach(entry => {
                    if (entry.isIntersecting) {
                        this._push('section_entered', null, entry.target.dataset.sectionCode);
                    }
                });
            }, { threshold: 0.5 });

            sections.forEach(sec => obs.observe(sec));
            this._observers = this._observers ?? [];
            this._observers.push(obs);
        }
    }

    /**
     * Detach all listeners and disconnect observers.
     */
    detach() {
        this._listeners.forEach(({ el, type, fn }) => el.removeEventListener(type, fn));
        this._listeners = [];
        (this._observers ?? []).forEach(obs => obs.disconnect());
        this._observers = [];
    }

    /**
     * Flush buffered events to the API.
     * Call this after a successful survey submit with the returned response_id.
     *
     * @param  {number} responseId
     * @returns {Promise<{stored: number}|null>}
     */
    async flush(responseId) {
        if (this.buffer.length === 0) return null;

        const events = [...this.buffer];
        this.buffer  = [];

        try {
            const res = await fetch(
                `${this.apiBase}/v1/surveys/${this.slug}/behavior`,
                {
                    method: 'POST',
                    headers: {
                        'Content-Type':  'application/json',
                        'Accept':        'application/json',
                        'Authorization': `Bearer ${this.token}`,
                    },
                    body: JSON.stringify({ response_id: responseId, events }),
                }
            );

            if (! res.ok) {
                // Put events back — caller can retry or ignore
                this.buffer.unshift(...events);
                return null;
            }

            return await res.json();
        } catch {
            this.buffer.unshift(...events);
            return null;
        }
    }

    // ── Internal helpers ────────────────────────────────────────────────────

    _on(el, type, fn) {
        el.addEventListener(type, fn);
        this._listeners.push({ el, type, fn });
    }

    _push(eventType, questionCode, eventValue = null) {
        this.buffer.push({
            question_code: questionCode,
            event_type:    eventType,
            event_value:   eventValue !== null ? String(eventValue) : null,
            occurred_at:   new Date().toISOString(),
        });
    }

    _onFocus(el, code) {
        this._focusTimes[code] = Date.now();
        this._push('question_focus', code);
    }

    _onBlur(el, code) {
        // Emit time_spent in seconds (rounded to 1 decimal)
        const start = this._focusTimes[code];
        if (start) {
            const seconds = ((Date.now() - start) / 1000).toFixed(1);
            this._push('time_spent', code, seconds);
            delete this._focusTimes[code];
        }
        this._push('question_blur', code);
    }

    _onChange(el, code) {
        const value = this._extractValue(el);

        if (value === '' || value === null) {
            this._push('answer_cleared', code);
        } else {
            this._push('answer_changed', code, value);
        }
    }

    _extractValue(el) {
        if (el.type === 'checkbox' || el.type === 'radio') {
            return el.checked ? el.value : '';
        }
        return el.value ?? '';
    }
}

/**
 * Alpine.js integration — use as an Alpine component data object.
 *
 * In your survey form component:
 *   x-data="surveyWithTracking({ slug: '...', bearerToken: '...' })"
 *
 * Then after submit:
 *   await this.tracker.flush(responseId);
 */
export function surveyWithTracking({ slug, bearerToken, apiBase = '/api' }) {
    const tracker = new SurveyBehaviorTracker({ slug, bearerToken, apiBase });

    return {
        tracker,
        responseId: null,

        initTracking() {
            this.$nextTick(() => {
                tracker.attach(this.$el);
            });
        },

        async submitAndTrack(submitFn) {
            const id = await submitFn();
            if (id) {
                this.responseId = id;
                await tracker.flush(id);
            }
            return id;
        },

        destroy() {
            tracker.detach();
        },
    };
}
