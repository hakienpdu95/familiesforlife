@extends('layouts.backend')
@section('title', 'Content Foundation theo chuyên mục')

@section('content')
<div x-data="categoryFoundationsPage({{ Js::from([
    'listUrl' => route('backend.api.coreideaextractor.category-foundations.list'),
    'upsertUrlTemplate' => route('backend.api.coreideaextractor.category-foundations.upsert', ['category' => '__UUID__']),
    'backUrl' => route('backend.coreideaextractor.index'),
]) }})">

    <div class="mb-5 flex items-center justify-between flex-wrap gap-2">
        <div>
            <h1 class="text-2xl font-bold text-base-content">Content Foundation theo chuyên mục</h1>
            <p class="text-sm text-base-content/50 mt-0.5">
                Ngữ cảnh biên tập lưu bền vững cho từng chuyên mục (trọng tâm nội dung, góc nhìn khác biệt, mục tiêu, đối
                tượng độc giả, giọng văn...) — trang "Trích xuất nội dung bài viết" sẽ tự nạp lại khi bạn chọn chuyên mục,
                không cần gõ lại mỗi lần. Nên viết ngắn gọn (1-2 câu/field) — nhồi quá nhiều chữ không giúp AI trả lời
                tốt hơn, chỉ loãng trọng tâm.
            </p>
        </div>
        <a :href="backUrl" class="btn btn-ghost btn-xs">&larr; Về trang trích xuất</a>
    </div>

    <p x-show="loading" class="text-sm text-base-content/50">Đang tải danh sách chuyên mục...</p>
    <p x-show="errorMessage" x-cloak class="text-error text-sm mb-3" x-text="errorMessage"></p>

    <div class="flex flex-col gap-2">
        <template x-for="cat in categories" :key="cat.uuid">
            <div class="card bg-base-100 shadow-sm border border-base-200">
                <div class="card-body py-3 px-4">
                    <button type="button" class="flex items-center justify-between w-full text-left" @click="cat._open = !cat._open">
                        <span class="font-medium text-sm" :style="`padding-left: ${cat.depth * 1.25}rem`" x-text="cat.name"></span>
                        <span class="flex items-center gap-2">
                            <span class="badge badge-xs" :class="cat.foundation ? 'badge-success' : 'badge-ghost'"
                                  x-text="cat.foundation ? 'Đã có foundation' : 'Chưa có'"></span>
                            <span class="text-base-content/40 text-xs" x-text="cat._open ? '▲' : '▼'"></span>
                        </span>
                    </button>

                    <div x-show="cat._open" x-cloak class="mt-3 flex flex-col gap-2">
                        <div class="form-control">
                            <label class="label py-0.5"><span class="label-text text-xs font-medium">Trọng tâm nội dung chuyên mục</span></label>
                            <textarea x-model="cat._form.core_focus" rows="2" placeholder="VD: Kiến thức ăn dặm khoa học cho trẻ 6-24 tháng"
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

                        <div class="flex items-center gap-2 mt-1">
                            <button type="button" class="btn btn-primary btn-xs" :disabled="cat._saving" @click="save(cat)">
                                <span x-show="!cat._saving">Lưu</span>
                                <span x-show="cat._saving" x-cloak>Đang lưu...</span>
                            </button>
                            <span x-show="cat._saved" x-cloak class="text-success text-xs">Đã lưu!</span>
                            <span x-show="cat._error" x-cloak class="text-error text-xs" x-text="cat._error"></span>
                        </div>
                    </div>
                </div>
            </div>
        </template>
    </div>
</div>
@endsection

@push('scripts')
<script>
document.addEventListener('alpine:init', () => {
    Alpine.data('categoryFoundationsPage', (serverData = {}) => {
        const { listUrl = '', upsertUrlTemplate = '', backUrl = '' } = serverData;

        const emptyForm = () => ({ core_focus: '', unique_angle: '', content_goals: '', audience: '', constraints: '', style_sample: '' });

        return {
            categories: [],
            loading: true,
            errorMessage: '',
            backUrl,

            async init() {
                try {
                    const res = await fetch(listUrl, { headers: { 'Accept': 'application/json' } });
                    const data = await res.json();

                    this.categories = (data.categories || []).map(cat => ({
                        ...cat,
                        _open: false,
                        _saving: false,
                        _saved: false,
                        _error: '',
                        _form: { ...emptyForm(), ...(cat.foundation || {}) },
                    }));
                } catch (e) {
                    console.error('[core-idea-extractor] failed to load categories', e);
                    this.errorMessage = 'Không tải được danh sách chuyên mục.';
                } finally {
                    this.loading = false;
                }
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
                        body: JSON.stringify(cat._form),
                    });

                    const data = await res.json().catch(() => ({}));

                    if (!res.ok) {
                        cat._error = data.message || 'Không lưu được, vui lòng thử lại.';
                        return;
                    }

                    cat.foundation = data.foundation;
                    cat._saved = true;
                    setTimeout(() => { cat._saved = false; }, 2000);
                } catch (e) {
                    console.error('[core-idea-extractor] failed to save foundation', e);
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
