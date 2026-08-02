@extends('layouts.backend')
@section('title', 'Content Foundation theo chuyên mục')

@section('content')
<div x-data="categoryFoundationsPage({{ Js::from([
    'listUrl' => route('backend.api.contentfoundation.category-foundations.list'),
    'upsertUrlTemplate' => route('backend.api.contentfoundation.category-foundations.upsert', ['category' => '__UUID__']),
    'staleAfterDays' => $staleAfterDays,
    'familyValues' => config('content_foundation.family_values.items', []),
    'familyValuesRef' => config('content_foundation.family_values.decision_ref'),
]) }})">

    <div class="mb-3 flex items-center justify-between flex-wrap gap-2">
        <div>
            <h1 class="text-2xl font-bold text-base-content">Content Foundation theo chuyên mục</h1>
            <p class="text-sm text-base-content/50 mt-0.5">
                Ngữ cảnh biên tập bền vững cho từng chuyên mục — 1 bộ tiêu chí có thể dùng chung cho nhiều chuyên mục.
                Dùng chung bởi mọi công cụ nghiên cứu ý tưởng nội dung (trích xuất bài viết, trích xuất video...).
                Chọn 1 chuyên mục bên trái để xem/chỉnh sửa.
            </p>
        </div>
    </div>

    <p x-show="loading" class="text-sm text-base-content/50">Đang tải danh sách chuyên mục...</p>
    <p x-show="errorMessage" x-cloak class="text-error text-sm mb-3" x-text="errorMessage"></p>

    <div x-show="!loading" x-cloak class="flex gap-4 items-start flex-col lg:flex-row">
        {{--
            Master-detail: cây chuyên mục (thu gọn/mở theo cha-con + tìm kiếm) bên trái, chỉ 1
            form chỉnh sửa hiển thị tại 1 thời điểm bên phải — tránh trang dài vô tận khi có
            hàng trăm chuyên mục (accordion phẳng cũ mở TẤT CẢ node trên cùng 1 trang cực dài, và
            multi-select "dùng chung" từng liệt kê lại toàn bộ chuyên mục dưới dạng checkbox).
        --}}
        <div class="w-full lg:w-72 shrink-0 flex flex-col border border-base-200 rounded-lg bg-base-100 max-h-[75vh]">
            <div class="p-2 border-b border-base-200">
                <input type="text" x-model="search" placeholder="Tìm chuyên mục..."
                       class="input input-sm input-bordered w-full">
            </div>
            <div class="flex-1 min-h-0 overflow-y-auto p-1">
                <template x-for="cat in visibleCategories()" :key="cat.uuid">
                    <div class="flex items-center gap-1 px-1.5 py-1.5 rounded cursor-pointer text-sm"
                         :class="selectedUuid === cat.uuid ? 'bg-primary/10 text-primary font-medium' : 'hover:bg-base-200'"
                         :style="`padding-left: ${0.375 + cat.depth * 1}rem`"
                         @click="select(cat)">
                        <button type="button" class="w-4 shrink-0 text-xs text-base-content/40 leading-none"
                                x-show="cat._hasChildren"
                                @click.stop="toggleCollapse(cat.uuid)"
                                x-text="collapsedUuids.has(cat.uuid) ? '▸' : '▾'"></button>
                        <span x-show="!cat._hasChildren" class="w-4 shrink-0"></span>
                        <span class="truncate flex-1" x-text="cat.name"></span>
                        <span class="w-1.5 h-1.5 rounded-full shrink-0"
                              :class="cat.foundation ? 'bg-success' : 'bg-base-300'"
                              :title="cat.foundation ? 'Đã có foundation' : 'Chưa có foundation'"></span>
                        <span x-show="cat.foundation?.shared_with?.length" x-cloak
                              class="badge badge-info badge-xs shrink-0"
                              :title="`Dùng chung với: ${(cat.foundation?.shared_with || []).map(c => c.name).join(', ')}`"
                              x-text="cat.foundation?.shared_with?.length"></span>
                    </div>
                </template>
                <p x-show="visibleCategories().length === 0" class="text-xs text-base-content/40 text-center py-6">
                    Không tìm thấy chuyên mục nào.
                </p>
            </div>
        </div>

        <div class="flex-1 min-w-0 w-full border border-base-200 rounded-lg bg-base-100 p-4">
            <template x-if="!selectedCategory()">
                <p class="text-sm text-base-content/40 text-center py-16">
                    Chọn 1 chuyên mục ở bên trái để xem/chỉnh sửa Content Foundation.
                </p>
            </template>

            <template x-if="selectedCategory()">
                <div x-data="{ get cat() { return selectedCategory() } }" class="flex flex-col gap-3">
                    <div class="flex items-center justify-between flex-wrap gap-2">
                        <h2 class="font-semibold text-base" x-text="cat.name"></h2>
                        <span class="flex items-center gap-2">
                            <span class="badge badge-xs" :class="cat.foundation ? 'badge-success' : 'badge-ghost'"
                                  x-text="cat.foundation ? 'Đã có foundation' : 'Chưa có'"></span>
                            <span x-show="cat.foundation?.shared_with?.length" x-cloak
                                  class="badge badge-info badge-xs"
                                  x-text="`Dùng chung: ${cat.foundation?.shared_with?.length || 0}`"></span>
                            <span x-show="cat.foundation?.updated_at" x-cloak
                                  class="badge badge-xs"
                                  :class="isFoundationStale(cat.foundation?.updated_at) ? 'badge-warning' : 'badge-ghost'"
                                  :title="isFoundationStale(cat.foundation?.updated_at) ? `Đã hơn ${staleAfterDays} ngày chưa cập nhật — cân nhắc ôn lại ngữ cảnh này` : ''"
                                  x-text="formatFoundationAge(cat.foundation?.updated_at)"></span>
                        </span>
                    </div>

                    <div class="form-control">
                        <label class="label py-0.5"><span class="label-text text-xs font-medium">Trọng tâm nội dung chuyên mục</span></label>
                        <textarea x-model="cat._form.core_focus" rows="2" placeholder="VD: Kiến thức ăn dặm khoa học cho trẻ 6-24 tháng"
                                  class="textarea textarea-bordered textarea-sm w-full"></textarea>
                    </div>
                    <div class="form-control">
                        <label class="label py-0.5">
                            <span class="label-text text-xs font-medium">Key insights for writers (5-7 gạch đầu dòng tóm tắt nhanh — đọc trước khi đọc hết core_focus/unique_angle)</span>
                        </label>
                        <textarea x-model="cat._form.writer_insights" rows="3" placeholder="VD: - Không viết về X, chỉ viết về Y&#10;- Motif lặp cần tránh: ..."
                                  class="textarea textarea-bordered textarea-sm w-full"></textarea>
                    </div>
                    <div class="form-control">
                        <label class="label py-0.5"><span class="label-text text-xs font-medium">Góc nhìn khác biệt (điều chỉ chuyên mục này viết được)</span></label>
                        <textarea x-model="cat._form.unique_angle" rows="2" placeholder="VD: Có đội ngũ chuyên gia dinh dưỡng nội bộ kiểm duyệt, không chỉ dịch lại nguồn ngoại"
                                  class="textarea textarea-bordered textarea-sm w-full"></textarea>
                    </div>
                    <div class="form-control">
                        <label class="label py-0.5"><span class="label-text text-xs font-medium">Mục tiêu nội dung</span></label>
                        <textarea x-model="cat._form.content_goals" rows="2" placeholder="VD: Tăng traffic tìm kiếm dài hạn, xây uy tín chuyên gia"
                                  class="textarea textarea-bordered textarea-sm w-full"></textarea>
                    </div>
                    <div class="form-control border border-base-200 rounded-md p-2.5 bg-base-200/30">
                        <label class="label py-0.5">
                            <span class="label-text text-xs font-medium">
                                Hệ giá trị gia đình chuyên mục này ưu tiên phục vụ
                                <span class="text-base-content/40 font-normal">(chuẩn nền tảng cố định — <span x-text="familyValuesRef"></span>, không phải văn bản tự viết)</span>
                            </span>
                        </label>
                        <div class="flex flex-wrap gap-x-4 gap-y-1.5 mt-0.5">
                            <template x-for="fv in familyValues" :key="fv.key">
                                <label class="label cursor-pointer justify-start gap-1.5 py-0" :title="fv.description">
                                    <input type="checkbox" class="checkbox checkbox-xs" :value="fv.key"
                                           :checked="cat._form.family_values_focus.includes(fv.key)"
                                           @change="toggleFamilyValue(cat, fv.key)">
                                    <span class="label-text text-xs" x-text="fv.label"></span>
                                </label>
                            </template>
                        </div>
                    </div>
                    <div class="form-control">
                        <label class="label py-0.5">
                            <span class="label-text text-xs font-medium">Pain points / câu hỏi thường gặp của độc giả (dựa trên nghiên cứu thực tế — khảo sát, feedback, câu hỏi lặp lại)</span>
                        </label>
                        <textarea x-model="cat._form.pain_points" rows="2" placeholder="VD: Con hay bị táo bón khi đổi sữa, mẹ không biết phân biệt sữa mát thật/giả"
                                  class="textarea textarea-bordered textarea-sm w-full"></textarea>
                    </div>
                    <div class="form-control">
                        <label class="label py-0.5">
                            <span class="label-text text-xs font-medium">Nghi ngờ / lý do chưa hành động (objections — khác pain_points, đây là điều khiến độc giả CHƯA TIN)</span>
                        </label>
                        <textarea x-model="cat._form.objections" rows="2" placeholder="VD: Sợ tốn tiền mà không hiệu quả, nghe nhiều quảng cáo sai sự thật nên cảnh giác"
                                  class="textarea textarea-bordered textarea-sm w-full"></textarea>
                    </div>
                    <div class="form-control">
                        <label class="label py-0.5">
                            <span class="label-text text-xs font-medium">Tiêu chí quyết định (điều độc giả dùng để so sánh/chọn giữa các lựa chọn)</span>
                        </label>
                        <textarea x-model="cat._form.decision_criteria" rows="2" placeholder="VD: Giá cả, có bác sĩ/chuyên gia tư vấn hay không, đánh giá thật từ người dùng khác"
                                  class="textarea textarea-bordered textarea-sm w-full"></textarea>
                    </div>
                    <div class="form-control">
                        <label class="label py-0.5">
                            <span class="label-text text-xs font-medium">Ý tưởng đã cân nhắc và quyết định KHÔNG viết (kèm lý do — Decision Log)</span>
                        </label>
                        <textarea x-model="cat._form.rejected_ideas" rows="2" placeholder="VD: 'So sánh giá sữa mát các hãng' — đã bỏ vì đối thủ đã làm rất kỹ, khó cạnh tranh"
                                  class="textarea textarea-bordered textarea-sm w-full"></textarea>
                    </div>
                    <div class="flex flex-wrap gap-3">
                        <div class="form-control flex-1 min-w-64">
                            <label class="label py-0.5"><span class="label-text text-xs font-medium">Đối tượng độc giả</span></label>
                            <input type="text" x-model="cat._form.audience" placeholder="VD: mẹ mới sinh con đầu lòng"
                                   class="input input-sm input-bordered w-full">
                        </div>
                        <div class="form-control flex-1 min-w-64">
                            <label class="label py-0.5"><span class="label-text text-xs font-medium">Ràng buộc / không muốn</span></label>
                            <input type="text" x-model="cat._form.constraints" placeholder="VD: không viết giọng hàn lâm"
                                   class="input input-sm input-bordered w-full">
                        </div>
                    </div>
                    <div class="form-control">
                        <label class="label py-0.5"><span class="label-text text-xs font-medium">Đoạn văn mẫu (giọng văn)</span></label>
                        <textarea x-model="cat._form.style_sample" rows="3"
                                  class="textarea textarea-bordered textarea-sm w-full text-xs"></textarea>
                    </div>

                    {{--
                        Combobox tìm-rồi-thêm thay cho checkbox-list liệt kê toàn bộ chuyên mục —
                        scale tốt với hàng trăm chuyên mục (chỉ render vài gợi ý khớp từ khoá thay
                        vì cả danh sách).
                    --}}
                    <div class="form-control">
                        <label class="label py-0.5">
                            <span class="label-text text-xs font-medium">Áp dụng bộ tiêu chí này cho các chuyên mục khác (dùng chung)</span>
                        </label>
                        <div class="flex flex-wrap gap-1.5 mb-1.5" x-show="cat._sharedWith.length" x-cloak>
                            <template x-for="uuid in cat._sharedWith" :key="uuid">
                                <span class="badge badge-outline gap-1.5">
                                    <span x-text="categoryNameByUuid(uuid)"></span>
                                    <button type="button" class="text-base-content/50 hover:text-error" @click="removeShared(cat, uuid)">✕</button>
                                </span>
                            </template>
                        </div>
                        <div class="relative">
                            <input type="text" x-model="shareQuery" placeholder="Tìm chuyên mục để thêm vào nhóm dùng chung..."
                                   class="input input-sm input-bordered w-full">
                            <ul x-show="shareQuery.trim() && shareSuggestions(cat).length" x-cloak
                                class="absolute z-10 mt-1 w-full bg-base-100 border border-base-300 rounded-md shadow-md max-h-48 overflow-y-auto text-sm">
                                <template x-for="s in shareSuggestions(cat)" :key="s.uuid">
                                    <li class="px-3 py-1.5 hover:bg-base-200 cursor-pointer flex items-center justify-between gap-2"
                                        @click="addShared(cat, s.uuid)">
                                        <span x-text="s.name"></span>
                                        <span x-show="s.foundation" class="badge badge-ghost badge-xs shrink-0">đang có bộ khác</span>
                                    </li>
                                </template>
                            </ul>
                            <p x-show="shareQuery.trim() && !shareSuggestions(cat).length" x-cloak class="text-xs text-base-content/40 mt-1">
                                Không tìm thấy chuyên mục phù hợp.
                            </p>
                        </div>
                        <p class="text-xs text-base-content/40 mt-1">
                            Thêm chuyên mục đang có bộ tiêu chí riêng sẽ THAY THẾ bộ cũ của chuyên mục đó bằng bộ này. Bấm ✕
                            để gỡ khỏi nhóm dùng chung (chuyên mục đó sẽ hết foundation nếu không thuộc nhóm nào khác).
                        </p>
                    </div>

                    <div class="flex items-center gap-2 mt-1">
                        <button type="button" class="btn btn-primary btn-xs" :disabled="cat._saving" @click="save(cat)">
                            <span x-show="!cat._saving">Lưu</span>
                            <span x-show="cat._saving" x-cloak>Đang lưu...</span>
                        </button>
                        <span x-show="cat._saved" x-cloak class="text-success text-xs">Đã lưu!</span>
                        <span x-show="cat._error" x-cloak class="text-error text-xs" x-text="cat._error"></span>
                    </div>
                </div>
            </template>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script>
document.addEventListener('alpine:init', () => {
    Alpine.data('categoryFoundationsPage', (serverData = {}) => {
        const { listUrl = '', upsertUrlTemplate = '', staleAfterDays = 180, familyValues = [], familyValuesRef = '' } = serverData;

        const emptyForm = () => ({ core_focus: '', writer_insights: '', unique_angle: '', content_goals: '', pain_points: '', objections: '', decision_criteria: '', family_values_focus: [], rejected_ideas: '', audience: '', constraints: '', style_sample: '' });

        return {
            categories: [],
            loading: true,
            errorMessage: '',
            staleAfterDays,
            familyValues,
            familyValuesRef,
            search: '',
            selectedUuid: null,
            shareQuery: '',
            /** uuid của chuyên mục đang THU GỌN (ẩn con) — mặc định mọi chuyên mục có con đều
             *  thu gọn (xem load()), để danh sách ban đầu chỉ hiện cấp cao nhất thay vì phẳng hết
             *  hàng trăm dòng cùng lúc. */
            collapsedUuids: new Set(),
            _collapseInitialized: false,

            async init() {
                await this.load();
                this.loading = false;
            },

            /**
             * §12.9 (N-N) — tách riêng khỏi init() để gọi lại được sau save() (1 lần lưu có thể
             * ảnh hưởng tới NHIỀU category cùng lúc — category vừa được thêm/gỡ khỏi nhóm dùng
             * chung — nên nạp lại toàn bộ danh sách là cách đơn giản, chắc chắn đúng nhất thay vì
             * tự vá state cục bộ cho từng category bị ảnh hưởng). `selectedUuid`/`collapsedUuids`
             * là state ĐỘC LẬP với mảng `categories` nên không cần khôi phục thủ công sau reload.
             */
            async load() {
                try {
                    const res = await fetch(listUrl, { headers: { 'Accept': 'application/json' } });
                    const data = await res.json();
                    const list = data.categories || [];

                    this.categories = list.map((cat, index) => {
                        // `updated_at`/`shared_with` chỉ để HIỂN THỊ (badge, combobox) — loại khỏi
                        // _form để không gửi kèm lên upsert() (server không khai field này trong
                        // rule validate(), gửi lên cũng bị bỏ qua, nhưng loại từ đầu cho sạch).
                        const { updated_at, shared_with, ...foundationFields } = cat.foundation || {};
                        const next = list[index + 1];

                        return {
                            ...cat,
                            _hasChildren: !!next && next.depth > cat.depth,
                            _saving: false,
                            _saved: false,
                            _error: '',
                            _form: { ...emptyForm(), ...foundationFields },
                            _sharedWith: (shared_with || []).map(c => c.uuid),
                        };
                    });

                    if (!this._collapseInitialized) {
                        this.collapsedUuids = new Set(this.categories.filter(c => c._hasChildren).map(c => c.uuid));
                        this._collapseInitialized = true;
                    }
                } catch (e) {
                    console.error('[content-foundation] failed to load categories', e);
                    this.errorMessage = 'Không tải được danh sách chuyên mục.';
                }
            },

            /**
             * Danh sách hiển thị ở cây bên trái — khi có từ khoá tìm kiếm, bỏ qua trạng thái thu
             * gọn và hiện MỌI chuyên mục khớp tên kèm tổ tiên của nó (để vẫn thấy ngữ cảnh cây);
             * khi không tìm kiếm, ẩn nhánh con của các uuid có trong `collapsedUuids`.
             */
            visibleCategories() {
                const q = this.search.trim().toLowerCase();

                if (q) {
                    const matchedUuids = new Set();

                    this.categories.forEach((c, index) => {
                        if (!c.name.toLowerCase().includes(q)) return;

                        matchedUuids.add(c.uuid);

                        let depth = c.depth;
                        for (let j = index - 1; j >= 0 && depth > 0; j--) {
                            if (this.categories[j].depth < depth) {
                                matchedUuids.add(this.categories[j].uuid);
                                depth = this.categories[j].depth;
                            }
                        }
                    });

                    return this.categories.filter(c => matchedUuids.has(c.uuid));
                }

                const result = [];
                let hideBelowDepth = null;

                for (const c of this.categories) {
                    if (hideBelowDepth !== null && c.depth > hideBelowDepth) continue;
                    hideBelowDepth = null;

                    result.push(c);

                    if (c._hasChildren && this.collapsedUuids.has(c.uuid)) {
                        hideBelowDepth = c.depth;
                    }
                }

                return result;
            },

            toggleCollapse(uuid) {
                if (this.collapsedUuids.has(uuid)) {
                    this.collapsedUuids.delete(uuid);
                } else {
                    this.collapsedUuids.add(uuid);
                }
                // Set không tự kích hoạt reactivity của Alpine khi mutate tại chỗ — gán lại tham
                // chiếu mới để visibleCategories() được tính lại.
                this.collapsedUuids = new Set(this.collapsedUuids);
            },

            select(cat) {
                this.selectedUuid = cat.uuid;
                this.shareQuery = '';
            },

            selectedCategory() {
                return this.categories.find(c => c.uuid === this.selectedUuid) || null;
            },

            categoryNameByUuid(uuid) {
                return this.categories.find(c => c.uuid === uuid)?.name || uuid;
            },

            /** Gợi ý combobox "dùng chung" — chỉ tính khi có từ khoá, giới hạn 8 kết quả (scale tốt với hàng trăm chuyên mục thay vì liệt kê hết). */
            shareSuggestions(cat) {
                const q = this.shareQuery.trim().toLowerCase();
                if (!q) return [];

                return this.categories
                    .filter(c => c.uuid !== cat.uuid && !cat._sharedWith.includes(c.uuid))
                    .filter(c => c.name.toLowerCase().includes(q))
                    .slice(0, 8);
            },

            addShared(cat, uuid) {
                if (!cat._sharedWith.includes(uuid)) cat._sharedWith.push(uuid);
                this.shareQuery = '';
            },

            removeShared(cat, uuid) {
                cat._sharedWith = cat._sharedWith.filter(u => u !== uuid);
            },

            toggleFamilyValue(cat, key) {
                const current = cat._form.family_values_focus || [];
                cat._form.family_values_focus = current.includes(key)
                    ? current.filter(k => k !== key)
                    : [...current, key];
            },

            /**
             * `content_foundations` có `timestamps()` ở DB từ đầu nhưng chưa từng lộ ra UI —
             * editor không có cách nào biết 1 foundation đã bao lâu chưa được ôn lại. Context
             * engineering: ngữ cảnh biên tập (core_focus/pain_points/rejected_ideas...) là tài sản
             * SỐNG, cần cập nhật theo thời gian (VD pain_points độc giả 2 năm trước có thể không
             * còn đúng), không phải cấu hình tĩnh viết 1 lần rồi bỏ quên — chỉ hiển thị NHẮC NHỞ
             * trực quan (badge-warning khi quá `staleAfterDays` ngày), KHÔNG tự động xoá/chặn gì.
             */
            foundationAgeDays(updatedAt) {
                if (!updatedAt) return null;

                return Math.floor((Date.now() - new Date(updatedAt).getTime()) / (1000 * 60 * 60 * 24));
            },

            formatFoundationAge(updatedAt) {
                const days = this.foundationAgeDays(updatedAt);

                if (days === null) return '';
                if (days < 1) return 'Cập nhật: hôm nay';
                if (days === 1) return 'Cập nhật: 1 ngày trước';
                if (days < 30) return `Cập nhật: ${days} ngày trước`;

                const months = Math.floor(days / 30);
                if (months < 12) return `Cập nhật: ${months} tháng trước`;

                return `Cập nhật: ${Math.floor(months / 12)} năm trước`;
            },

            isFoundationStale(updatedAt) {
                const days = this.foundationAgeDays(updatedAt);

                return days !== null && days >= this.staleAfterDays;
            },

            async save(cat) {
                cat._saving = true;
                cat._saved = false;
                cat._error = '';

                const csrf = document.querySelector('meta[name=csrf-token]')?.content ?? '';
                const url = upsertUrlTemplate.replace('__UUID__', cat.uuid);

                try {
                    const res = await fetch(url, {
                        method: 'PUT',
                        headers: {
                            'Content-Type':    'application/json',
                            'X-CSRF-TOKEN':     csrf,
                            'X-Requested-With': 'XMLHttpRequest',
                            'Accept':           'application/json',
                        },
                        body: JSON.stringify({ ...cat._form, category_uuids: cat._sharedWith }),
                    });

                    const data = await res.json().catch(() => ({}));

                    if (!res.ok) {
                        cat._error = data.message || 'Không lưu được, vui lòng thử lại.';
                        return;
                    }

                    // 1 lần lưu có thể đổi nhóm dùng chung của NHIỀU category khác (thêm/gỡ) —
                    // nạp lại toàn bộ danh sách để mọi category liên quan hiển thị đúng trạng thái
                    // mới nhất, thay vì chỉ vá state của riêng `cat` này.
                    await this.load();
                    const saved = this.categories.find(c => c.uuid === cat.uuid);
                    if (saved) {
                        saved._saved = true;
                        setTimeout(() => { saved._saved = false; }, 2000);
                    }
                } catch (e) {
                    console.error('[content-foundation] failed to save foundation', e);
                    cat._error = 'Lỗi kết nối. Vui lòng thử lại.';
                } finally {
                    cat._saving = false;
                }
            },
        };
    });
});
</script>
@endpush
