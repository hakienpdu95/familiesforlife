/**
 * Modules/ContentCalendar/resources/assets/js/pages/board.js
 *
 * docs/form-ui-spec.md §25 (anti-pattern JS) — Alpine.data(...) đăng ký trong file JS riêng, sự
 * kiện 'alpine:init' (KHÔNG inline trong <script> blade — đã từng gây lỗi "Alpine is not
 * defined" do script inline chạy đồng bộ trước khi app.js/module Alpine kịp load).
 */
import { syncEntryDialogWidgets } from '../shared/entry-dialog-select.js';

document.addEventListener('alpine:init', () => {
    Alpine.data('contentCalendarBoard', (serverData = {}) => ({
        ...serverData,
        entries: [],
        loading: false,
        saving: false,
        errorMessage: '',
        fieldErrors: {},
        editingEntry: null,
        filters: { categoryId: '', assignedTo: '', includeDone: false },
        form: { post_category_id: '', title: '', brief: '', origin: 'manual', origin_note: '', target_publish_date: '', assigned_to: '' },

        init() {
            this.loadEntries();
        },

        csrfHeaders() {
            return {
                'Content-Type': 'application/json',
                'Accept': 'application/json',
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.content ?? '',
            };
        },

        async loadEntries() {
            this.loading = true;
            const params = new URLSearchParams();
            if (this.filters.categoryId) params.set('category_id', this.filters.categoryId);
            if (this.filters.assignedTo) params.set('assigned_to', this.filters.assignedTo);
            if (this.filters.includeDone) params.set('include_done', '1');
            params.set('per_page', '200');

            try {
                const res = await fetch(`${this.listUrl}?${params.toString()}`, { headers: { Accept: 'application/json' } });
                const json = await res.json();
                this.entries = json.data ?? [];
            } finally {
                this.loading = false;
            }
        },

        entriesByStatus(status) {
            return this.entries.filter((e) => e.status === status);
        },

        /**
         * docs/form-ui-spec.md §22 + Post::admin.categories.create — thụt lề theo `cat.depth`
         * (ký tự full-width space '　' + dấu "– ") để thể hiện phân cấp cha/con trong <option>
         * phẳng, cùng đúng convention Post module đang dùng cho dropdown "Danh mục cha".
         */
        categoryOptionLabel(cat) {
            return '　'.repeat(cat.depth) + (cat.depth > 0 ? '– ' : '') + cat.name;
        },

        /**
         * Đồ thị THUẦN client-side, chỉ để dựng option cho <select> — server
         * (ChangeCalendarEntryStatusAction) là nơi enforce THẬT, đây chỉ là gợi ý UX.
         */
        transitionGraph: {
            idea: ['planned', 'dropped'],
            planned: ['drafting', 'dropped'],
            drafting: ['blocked', 'ready', 'dropped'],
            blocked: ['drafting', 'dropped'],
            ready: ['drafting'], // 'done' KHÔNG có ở đây — chỉ hệ thống được set (§5.3.1)
            done: [],
            dropped: [],
        },

        nextStatusOptions(entry) {
            // Sau khi liên kết PostArticle: chỉ còn 'dropped' là target hợp lệ (§5.3.1).
            const allowed = entry.is_linked ? ['dropped'] : this.transitionGraph[entry.status] ?? [];

            return this.statuses.filter((s) => allowed.includes(s.value));
        },

        async changeStatus(entry, target) {
            if (!target) return;

            const res = await fetch(this.statusUrlTemplate.replace('__UUID__', entry.uuid), {
                method: 'PATCH',
                headers: this.csrfHeaders(),
                body: JSON.stringify({ status: target }),
            });

            if (!res.ok) {
                const json = await res.json().catch(() => ({}));
                window.Toast?.error(json.message ?? 'Không chuyển được trạng thái.');
                return;
            }

            await this.loadEntries();
        },

        async promptLinkArticle(entry) {
            const uuid = prompt('Dán UUID bài viết (Post) muốn gắn với kế hoạch này:');
            if (!uuid) return;

            const res = await fetch(this.linkArticleUrlTemplate.replace('__UUID__', entry.uuid), {
                method: 'POST',
                headers: this.csrfHeaders(),
                body: JSON.stringify({ article_uuid: uuid.trim() }),
            });

            const json = await res.json().catch(() => ({}));
            if (!res.ok) {
                window.Toast?.error(json.message ?? 'Không gắn được bài viết.');
                return;
            }

            window.Toast?.success('Đã gắn kế hoạch với bài viết.');
            await this.loadEntries();
        },

        async deleteEntry(entry) {
            if (!confirm(`Xoá kế hoạch "${entry.title}"?`)) return;

            const res = await fetch(this.destroyUrlTemplate.replace('__UUID__', entry.uuid), {
                method: 'DELETE',
                headers: this.csrfHeaders(),
            });

            if (!res.ok) {
                window.Toast?.error('Không xoá được kế hoạch.');
                return;
            }

            await this.loadEntries();
        },

        openCreateModal() {
            this.editingEntry = null;
            this.errorMessage = '';
            this.fieldErrors = {};
            this.form = { post_category_id: '', title: '', brief: '', origin: 'manual', origin_note: '', target_publish_date: '', assigned_to: '' };
            this.$refs.entryDialog.showModal();
            this.$nextTick(() => syncEntryDialogWidgets(this.$refs.entryDialog));
        },

        openEditModal(entry) {
            this.editingEntry = entry;
            this.errorMessage = '';
            this.fieldErrors = {};
            this.form = {
                post_category_id: entry.category?.id ?? '',
                title: entry.title,
                brief: entry.brief ?? '',
                origin: entry.origin,
                origin_note: entry.origin_note ?? '',
                target_publish_date: entry.target_publish_date ?? '',
                assigned_to: entry.assigned_to?.id ?? '',
            };
            this.$refs.entryDialog.showModal();
            this.$nextTick(() => syncEntryDialogWidgets(this.$refs.entryDialog));
        },

        closeModal() {
            this.$refs.entryDialog.close();
        },

        async submitForm() {
            this.saving = true;
            this.errorMessage = '';
            this.fieldErrors = {};

            const payload = { ...this.form };
            if (payload.assigned_to === '') payload.assigned_to = null;
            if (payload.target_publish_date === '') payload.target_publish_date = null;

            const url = this.editingEntry
                ? this.updateUrlTemplate.replace('__UUID__', this.editingEntry.uuid)
                : this.storeUrl;
            const method = this.editingEntry ? 'PUT' : 'POST';

            try {
                const res = await fetch(url, { method, headers: this.csrfHeaders(), body: JSON.stringify(payload) });
                const json = await res.json().catch(() => ({}));

                if (!res.ok) {
                    this.fieldErrors = json.errors ?? {};
                    // json.message (khi có $exception->getMessage() từ ValidationException) tự
                    // sinh dạng "Vui lòng chọn category. (and 1 more error)" — trộn tiếng Việt với
                    // hậu tố tiếng Anh của Laravel. Khi ĐÃ có fieldErrors (từng field tự hiện lỗi
                    // riêng rồi), banner chỉ cần câu chung tiếng Việt, không dùng json.message.
                    this.errorMessage = Object.keys(this.fieldErrors).length
                        ? 'Vui lòng kiểm tra lại các trường bên dưới.'
                        : (json.message ?? 'Vui lòng kiểm tra lại các trường bên dưới.');
                    return;
                }

                this.closeModal();
                await this.loadEntries();
            } finally {
                this.saving = false;
            }
        },
    }));
});
