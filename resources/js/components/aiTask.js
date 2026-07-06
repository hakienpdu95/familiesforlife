/**
 * resources/js/components/aiTask.js
 *
 * Factory function for the AI Copilot task widget.
 * Exposed as window.aiTask and used directly in x-data attributes.
 *
 * Usage in blade:
 *   <div x-data="aiTask({ agentSlug: 'sop.draft', variables: {...} })">
 *     <button @click="run()" :disabled="loading">...</button>
 *     <p x-show="error" x-text="error"></p>
 *     <div x-show="output" x-text="output"></div>
 *   </div>
 *
 * Or with extra methods:
 *   <div x-data="{ ...aiTask({...}), apply() { ... } }">
 */
export function aiTask({
    agentSlug,
    variables    = {},
    subjectType  = null,
    subjectId    = null,
    onDone       = null,
} = {}) {
    return {
        loading: false,
        error:   null,
        output:  null,
        _poll:   null,

        async run() {
            this.loading = true;
            this.error   = null;
            this.output  = null;

            try {
                const res = await fetch('/dashboard/ai/execute', {
                    method:      'POST',
                    credentials: 'same-origin',
                    headers: {
                        'Content-Type':     'application/json',
                        'X-CSRF-TOKEN':     document.querySelector('meta[name="csrf-token"]')?.content ?? '',
                        'X-Requested-With': 'XMLHttpRequest',
                    },
                    body: JSON.stringify({
                        agent_slug:   agentSlug,
                        variables:    variables,
                        subject_type: subjectType,
                        subject_id:   subjectId,
                    }),
                });

                if (res.status === 402) {
                    this.error = 'Đã hết quota AI tháng này. Vui lòng nâng cấp gói.';
                    return;
                }
                if (res.status === 403) {
                    this.error = 'Bạn không có quyền sử dụng tính năng AI.';
                    return;
                }
                if (!res.ok) {
                    this.error = `Lỗi server (${res.status}). Vui lòng thử lại.`;
                    return;
                }

                const data = await res.json();

                if (data.status === 'done') {
                    this._done(data.output);
                } else {
                    this._startPoll(data.uuid);
                }
            } catch {
                this.error = 'Lỗi kết nối. Vui lòng thử lại.';
            } finally {
                if (!this._poll) this.loading = false;
            }
        },

        _done(text) {
            this.output = text;
            if (typeof onDone === 'function') onDone(text);
        },

        _startPoll(uuid) {
            let attempts = 0;
            this._poll = setInterval(async () => {
                attempts++;
                if (attempts > 30) {
                    clearInterval(this._poll);
                    this._poll   = null;
                    this.loading = false;
                    this.error   = 'AI mất quá nhiều thời gian để phản hồi.';
                    return;
                }
                try {
                    const res  = await fetch(`/dashboard/ai/requests/${uuid}`, {
                        credentials: 'same-origin',
                        headers: { 'X-Requested-With': 'XMLHttpRequest' },
                    });
                    const data = await res.json();
                    if (data.status === 'done') {
                        clearInterval(this._poll);
                        this._poll   = null;
                        this.loading = false;
                        this._done(data.output);
                    } else if (data.status === 'failed') {
                        clearInterval(this._poll);
                        this._poll   = null;
                        this.loading = false;
                        this.error   = 'AI xử lý thất bại.';
                    }
                } catch {}
            }, 1500);
        },

        destroy() {
            if (this._poll) {
                clearInterval(this._poll);
                this._poll = null;
            }
        },
    };
}
