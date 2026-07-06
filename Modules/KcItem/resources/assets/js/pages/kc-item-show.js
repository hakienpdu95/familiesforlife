/**
 * Alpine.js components for KcItem show page.
 * - kcAccessControl: manage permissions tab
 * - kcFeedback: feedback/rating tab
 */

window.kcAccessControl = function ({ indexUrl, storeUrl, destroyBase }) {
    return {
        controls: [],
        loading: false,
        saving: false,
        error: '',
        form: {
            target_type: 'user',
            target_id: '',
            permission: 'view',
            expired_at: '',
        },

        async init() {
            await this.loadControls();
        },

        async loadControls() {
            this.loading = true;
            try {
                const res = await fetch(indexUrl, {
                    headers: { 'X-Requested-With': 'XMLHttpRequest', Accept: 'application/json' },
                });
                const json = await res.json();
                this.controls = json.data || [];
            } catch {
                this.error = 'Không thể tải danh sách phân quyền.';
            } finally {
                this.loading = false;
            }
        },

        async grantAccess() {
            this.error = '';
            if (!this.form.target_id) {
                this.error = 'Vui lòng nhập ID đối tượng.';
                return;
            }
            this.saving = true;
            try {
                const res = await fetch(storeUrl, {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        Accept: 'application/json',
                        'X-CSRF-TOKEN': document.querySelector('meta[name=csrf-token]')?.content,
                    },
                    body: JSON.stringify(this.form),
                });
                const json = await res.json();
                if (!res.ok) {
                    this.error = json.message || 'Có lỗi xảy ra.';
                    return;
                }
                this.controls.unshift(json.data);
                this.form.target_id = '';
                this.form.expired_at = '';
            } catch {
                this.error = 'Có lỗi kết nối.';
            } finally {
                this.saving = false;
            }
        },

        async revokeAccess(ac) {
            if (!confirm('Thu hồi quyền này?')) return;
            const url = destroyBase + ac.id;
            try {
                const res = await fetch(url, {
                    method: 'DELETE',
                    headers: {
                        Accept: 'application/json',
                        'X-CSRF-TOKEN': document.querySelector('meta[name=csrf-token]')?.content,
                    },
                });
                if (res.ok) {
                    this.controls = this.controls.filter(c => c.id !== ac.id);
                }
            } catch {
                alert('Không thể thu hồi quyền.');
            }
        },
    };
};

window.kcFeedback = function ({ upsertUrl, initialRating, initialComment, initialHelpful, avgRating, totalFeedback, helpfulPercent }) {
    return {
        rating: initialRating,
        comment: initialComment || '',
        helpful: initialHelpful,
        saving: false,
        successMsg: '',
        errorMsg: '',
        avgRating,
        totalFeedback,
        helpfulPercent,

        async submitFeedback() {
            this.successMsg = '';
            this.errorMsg = '';

            if (!this.rating && this.helpful === null && !this.comment.trim()) {
                this.errorMsg = 'Vui lòng nhập ít nhất một trường đánh giá.';
                return;
            }

            this.saving = true;
            try {
                const res = await fetch(upsertUrl, {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        Accept: 'application/json',
                        'X-CSRF-TOKEN': document.querySelector('meta[name=csrf-token]')?.content,
                    },
                    body: JSON.stringify({
                        rating: this.rating,
                        comment: this.comment || null,
                        is_helpful: this.helpful,
                    }),
                });
                const json = await res.json();
                if (!res.ok) {
                    this.errorMsg = json.message || 'Có lỗi xảy ra.';
                    return;
                }
                this.successMsg = json.message || 'Đã lưu đánh giá.';
                setTimeout(() => { this.successMsg = ''; }, 3000);
            } catch {
                this.errorMsg = 'Có lỗi kết nối.';
            } finally {
                this.saving = false;
            }
        },
    };
};
