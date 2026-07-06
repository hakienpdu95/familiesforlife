/**
 * evaluation-form.js
 *
 * Alpine component rcEvaluationCreate
 * Dữ liệu ban đầu được truyền qua <script type="application/json" id="eval-initial-data">
 * trong blade, để tránh PHP logic trong blade <script>.
 * URL được truyền qua data-submit-url và data-redirect trên wrapper element.
 */

document.addEventListener('alpine:init', () => {
    Alpine.data('rcEvaluationCreate', () => {
        const raw = JSON.parse(
            document.getElementById('eval-initial-data')?.textContent ?? '{}'
        );

        return {
            form: {
                overall_score:  raw.overall_score  ?? 7,
                verdict:        raw.verdict        ?? '',
                strengths:      raw.strengths      ?? '',
                weaknesses:     raw.weaknesses     ?? '',
                recommendation: raw.recommendation ?? '',
                criteria:       raw.criteria       ?? [],
            },

            addCriterion() {
                this.form.criteria.push({ criterion_name: '', score: 7, comment: '' });
            },

            removeCriterion(idx) {
                this.form.criteria.splice(idx, 1);
            },

            submitEvaluation() {
                if (!this.form.verdict) {
                    window.Toast?.warning('Vui lòng chọn kết luận');
                    return;
                }

                const el       = this.$el;
                const submitUrl = el.dataset.submitUrl;
                const redirect  = el.dataset.redirect;
                const csrf      = document.querySelector('meta[name="csrf-token"]')?.content ?? '';

                fetch(submitUrl, {
                    method:  'POST',
                    headers: {
                        'Content-Type':     'application/json',
                        'X-CSRF-TOKEN':     csrf,
                        'X-Requested-With': 'XMLHttpRequest',
                    },
                    body: JSON.stringify(this.form),
                })
                .then(r => r.json())
                .then(data => {
                    if (data.message) {
                        window.Toast?.success(data.message);
                        setTimeout(() => { window.location.href = redirect; }, 800);
                    } else {
                        window.Toast?.error('Có lỗi xảy ra');
                    }
                })
                .catch(e => {
                    console.error(e);
                    window.Toast?.error('Lỗi khi nộp đánh giá');
                });
            },
        };
    });
});
