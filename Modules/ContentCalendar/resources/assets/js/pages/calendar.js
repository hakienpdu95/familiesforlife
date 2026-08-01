/**
 * Modules/ContentCalendar/resources/assets/js/pages/calendar.js
 *
 * docs/form-ui-spec.md §25 (anti-pattern JS) — Alpine.data(...) đăng ký trong file JS riêng, sự
 * kiện 'alpine:init' (KHÔNG inline trong <script> blade).
 */
import { syncEntryDialogWidgets } from '../shared/entry-dialog-select.js';

document.addEventListener('alpine:init', () => {
    Alpine.data('contentCalendarMonthView', (serverData = {}) => ({
        ...serverData,
        entries: [],
        unscheduled: [],
        loading: false,
        saving: false,
        errorMessage: '',
        fieldErrors: {},
        editingEntry: null,
        selectedMonth: new Date().toISOString().slice(0, 7), // 'YYYY-MM' — client clock, chỉ để chọn tháng hiển thị mặc định
        weeksFlat: [],
        weekdayLabels: ['T2', 'T3', 'T4', 'T5', 'T6', 'T7', 'CN'],
        form: { post_category_id: '', title: '', brief: '', origin: 'manual', origin_note: '', target_publish_date: '', assigned_to: '' },

        // Literal đầy đủ (KHÔNG ghép chuỗi runtime) để Tailwind scan tĩnh nhận diện được — ghép
        // kiểu `bg-${tone}/15` sẽ không có trong bundle CSS vì Tailwind không thấy được chuỗi
        // hoàn chỉnh lúc build. Cùng 7 trạng thái với CalendarEntryStatus::badgeClass().
        statusToneClasses: {
            idea: 'bg-base-300/50 text-base-content/70 border-base-300',
            planned: 'bg-info/10 text-info border-info/30',
            drafting: 'bg-primary/10 text-primary border-primary/30',
            blocked: 'bg-warning/10 text-warning border-warning/30',
            ready: 'bg-accent/10 text-accent border-accent/30',
            done: 'bg-success/10 text-success border-success/30',
            dropped: 'bg-neutral/10 text-neutral border-neutral/30',
        },
        statusDotClasses: {
            idea: 'bg-base-300', planned: 'bg-info', drafting: 'bg-primary',
            blocked: 'bg-warning', ready: 'bg-accent', done: 'bg-success', dropped: 'bg-neutral',
        },

        init() {
            this.loadEntries();
        },

        chipClass(status) {
            return this.statusToneClasses[status] ?? this.statusToneClasses.idea;
        },

        dotClass(status) {
            return this.statusDotClasses[status] ?? this.statusDotClasses.idea;
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
            try {
                // Lịch nhìn TƯƠNG LAI (includeDone mặc định false) — lấy hết entry đang hoạt động
                // 1 lần, bucket theo ngày ở client (đơn giản hơn gọi lại API mỗi lần đổi tháng, số
                // lượng entry đang hoạt động vốn đã bị chặn bởi board không lưu trữ).
                const res = await fetch(`${this.listUrl}?per_page=500`, { headers: { Accept: 'application/json' } });
                const json = await res.json();
                this.entries = json.data ?? [];
            } finally {
                this.loading = false;
                this.renderMonth();
            }
        },

        monthLabel() {
            const [year, month] = this.selectedMonth.split('-').map(Number);
            return `Tháng ${month}, ${year}`;
        },

        /**
         * docs/form-ui-spec.md §22 + Post::admin.categories.create — thụt lề theo `cat.depth`
         * (ký tự full-width space '　' + dấu "– ") để thể hiện phân cấp cha/con trong <option>
         * phẳng, cùng đúng convention Post module đang dùng cho dropdown "Danh mục cha".
         */
        categoryOptionLabel(cat) {
            return '　'.repeat(cat.depth) + (cat.depth > 0 ? '– ' : '') + cat.name;
        },

        goToMonth(offset) {
            let [year, month] = this.selectedMonth.split('-').map(Number);
            month += offset;
            if (month < 1) { month = 12; year -= 1; }
            if (month > 12) { month = 1; year += 1; }
            this.selectedMonth = `${year}-${String(month).padStart(2, '0')}`;
            this.renderMonth();
        },

        goToToday() {
            this.selectedMonth = new Date().toISOString().slice(0, 7);
            this.renderMonth();
        },

        renderMonth() {
            this.unscheduled = this.entries.filter((e) => !e.target_publish_date);

            const byDate = {};
            this.entries.forEach((e) => {
                if (!e.target_publish_date) return;
                (byDate[e.target_publish_date] ??= []).push(e);
            });

            const todayStr = new Date().toISOString().slice(0, 10);
            const [year, month] = this.selectedMonth.split('-').map(Number); // month: 1-12
            const firstOfMonth = new Date(Date.UTC(year, month - 1, 1));
            const daysInMonth = new Date(Date.UTC(year, month, 0)).getUTCDate();
            // Thứ trong tuần bắt đầu từ Thứ 2 (ISO) — getUTCDay(): 0=CN..6=T7 → lệch 1 để CN về cuối tuần.
            const leading = (firstOfMonth.getUTCDay() + 6) % 7;

            const cells = [];
            for (let i = 0; i < leading; i++) {
                cells.push({ day: '', inMonth: false, dateStr: null, isToday: false, entries: [] });
            }
            for (let d = 1; d <= daysInMonth; d++) {
                const dateStr = `${year}-${String(month).padStart(2, '0')}-${String(d).padStart(2, '0')}`;
                cells.push({ day: d, inMonth: true, dateStr, isToday: dateStr === todayStr, entries: byDate[dateStr] ?? [] });
            }
            while (cells.length % 7 !== 0) {
                cells.push({ day: '', inMonth: false, dateStr: null, isToday: false, entries: [] });
            }

            this.weeksFlat = cells;
        },

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
            if (!entry) return [];
            const allowed = entry.is_linked ? ['dropped'] : this.transitionGraph[entry.status] ?? [];

            return this.statuses.filter((s) => allowed.includes(s.value));
        },

        async changeStatus(target) {
            if (!target || !this.editingEntry) return;

            const res = await fetch(this.statusUrlTemplate.replace('__UUID__', this.editingEntry.uuid), {
                method: 'PATCH',
                headers: this.csrfHeaders(),
                body: JSON.stringify({ status: target }),
            });

            const json = await res.json().catch(() => ({}));
            if (!res.ok) {
                window.Toast?.error(json.message ?? 'Không chuyển được trạng thái.');
                return;
            }

            this.editingEntry = json.entry;
            await this.loadEntries();
        },

        async promptLinkArticle() {
            if (!this.editingEntry) return;
            const uuid = prompt('Dán UUID bài viết (Post) muốn gắn với kế hoạch này:');
            if (!uuid) return;

            const res = await fetch(this.linkArticleUrlTemplate.replace('__UUID__', this.editingEntry.uuid), {
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
            this.editingEntry = json.entry;
            await this.loadEntries();
        },

        async deleteEntry() {
            if (!this.editingEntry) return;
            if (!confirm(`Xoá kế hoạch "${this.editingEntry.title}"?`)) return;

            const res = await fetch(this.destroyUrlTemplate.replace('__UUID__', this.editingEntry.uuid), {
                method: 'DELETE',
                headers: this.csrfHeaders(),
            });

            if (!res.ok) {
                window.Toast?.error('Không xoá được kế hoạch.');
                return;
            }

            this.closeModal();
            await this.loadEntries();
        },

        openCreateModal(dateStr) {
            this.editingEntry = null;
            this.errorMessage = '';
            this.fieldErrors = {};
            this.form = { post_category_id: '', title: '', brief: '', origin: 'manual', origin_note: '', target_publish_date: dateStr ?? '', assigned_to: '' };
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
