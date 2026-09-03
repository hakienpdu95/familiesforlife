@extends('layouts.backend')
@section('title', 'Tạo Series Bible mới')

@section('content')
<div x-data="videoSeriesPromptStudioForm({{ Js::from([
    'categoryFoundationDetailUrlTemplate' => route('backend.api.contentfoundation.category-foundations.show', ['category' => '__UUID__']),
    'categories' => $categoryFoundations,
]) }})">

    <div class="mb-5 flex items-start justify-between flex-wrap gap-2">
        <div>
            <h1 class="text-2xl font-bold text-base-content">Tạo Series Bible mới</h1>
            <p class="text-sm text-base-content/50 mt-0.5">
                Ghép sẵn 1 câu lệnh (prompt) hoàn chỉnh để bạn copy sang ChatGPT/Claude — thiết kế concept, khung 1
                tập chuẩn lặp lại, và dàn ý liền mạch cho 5-10 tập đầu tiên của cả chuỗi video. Công cụ này KHÔNG gọi
                AI trong app, chỉ sinh và lưu lại câu lệnh để bạn tự dán sang công cụ AI đang dùng.
            </p>
        </div>
        <a href="{{ route('backend.videoseriespromptstudio.index') }}" class="btn btn-ghost btn-xs">← Danh sách</a>
    </div>

    <div class="card bg-base-100 shadow-sm border border-base-200">
        <div class="card-body py-4 px-4">
            <form method="POST" action="{{ route('backend.videoseriespromptstudio.store') }}" class="flex flex-col gap-3 max-w-2xl">
                @csrf

                <div class="form-control">
                    <label class="label py-0.5"><span class="label-text text-xs font-medium">Tên gọi cho prompt này *</span></label>
                    <input type="text" name="label" value="{{ old('label') }}" maxlength="150" required
                           placeholder="VD: Series chăm sóc trẻ sơ sinh — Nhật Ký Làm Mẹ"
                           class="input input-sm input-bordered w-full @error('label') input-error @enderror">
                    @error('label')<p class="text-xs text-error mt-1">{{ $message }}</p>@enderror
                </div>

                <div class="form-control">
                    <label class="label py-0.5"><span class="label-text text-xs font-medium">Chủ đề cốt lõi của Series *</span></label>
                    <input type="text" name="series_topic" value="{{ old('series_topic') }}" maxlength="255" required
                           placeholder="VD: Chăm sóc trẻ sơ sinh, Giải quyết khủng hoảng tuổi lên 3"
                           class="input input-sm input-bordered w-full @error('series_topic') input-error @enderror">
                    @error('series_topic')<p class="text-xs text-error mt-1">{{ $message }}</p>@enderror
                </div>

                <div class="form-control">
                    <label class="label py-0.5">
                        <span class="label-text text-xs font-medium">Góc nhìn / Vibe của kênh</span>
                        <span class="label-text-alt text-xs text-base-content/40">Tính cách/chất giọng — KHÔNG phải mục tiêu kinh doanh</span>
                    </label>
                    <input type="text" name="pov" value="{{ old('pov') }}" maxlength="500"
                           placeholder="VD: Ông bố lóng ngóng lần đầu chăm con; Mẹ bỉm sữa văn phòng, không tô hồng; Gia đình lầy lội hay trêu chọc nhau"
                           class="input input-sm input-bordered w-full">
                </div>

                <div class="form-control">
                    <label class="label py-0.5"><span class="label-text text-xs font-medium">Mục tiêu chuỗi video</span></label>
                    <textarea name="business_goal" rows="2" maxlength="2000"
                              placeholder="VD: Tăng độ nhận diện thương hiệu, giáo dục khách hàng, hay chốt sale trực tiếp"
                              class="textarea textarea-bordered textarea-sm w-full">{{ old('business_goal') }}</textarea>
                </div>

                <div class="flex flex-wrap gap-3">
                    <div class="form-control w-72">
                        <label class="label py-0.5"><span class="label-text text-xs font-medium">Nền tảng mục tiêu</span></label>
                        <select name="platform" class="select select-sm select-bordered w-full">
                            @foreach (config('video_series_prompt_studio.platform.options', []) as $key => $option)
                                <option value="{{ $key }}" @selected(old('platform', config('video_series_prompt_studio.platform.default')) === $key)>
                                    {{ $option['label'] }}
                                </option>
                            @endforeach
                        </select>
                    </div>
                    <div class="form-control w-48">
                        <label class="label py-0.5"><span class="label-text text-xs font-medium">Số tập cần lên dàn ý</span></label>
                        <input type="number" name="episode_count" min="{{ config('video_series_prompt_studio.content_arc.min_episode_count', 5) }}"
                               max="{{ config('video_series_prompt_studio.content_arc.max_episode_count', 10) }}"
                               value="{{ old('episode_count', config('video_series_prompt_studio.content_arc.default_episode_count', 5)) }}"
                               class="input input-sm input-bordered w-full">
                        <p class="text-xs text-base-content/40 mt-1">Từ {{ config('video_series_prompt_studio.content_arc.min_episode_count', 5) }} đến {{ config('video_series_prompt_studio.content_arc.max_episode_count', 10) }} tập</p>
                    </div>
                </div>

                <div class="divider my-1"></div>

                <div class="form-control">
                    <label class="label py-0.5"><span class="label-text text-xs font-medium">Chuyên mục (để lấy ngữ cảnh thương hiệu)</span></label>
                    <select name="post_category_uuid" x-model="selectedCategoryUuid" @change="applyCategoryFoundation()" class="select select-sm select-bordered w-full">
                        <option value="">— Chưa chọn —</option>
                        <template x-for="cat in categories" :key="cat.uuid">
                            <option :value="cat.uuid" x-text="'　'.repeat(cat.depth) + cat.name"></option>
                        </template>
                    </select>
                    <p x-show="selectedCategoryUuid && loadingFoundation" x-cloak class="text-xs text-base-content/40 mt-1">Đang tải ngữ cảnh chuyên mục...</p>
                    <p x-show="!loadingFoundation && foundationSummary()" x-cloak class="text-xs text-base-content/40 mt-1" x-text="foundationSummary()"></p>
                </div>

                <button type="submit" class="btn btn-primary btn-sm w-fit mt-2">Sinh prompt</button>
            </form>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script>
document.addEventListener('alpine:init', () => {
    Alpine.data('videoSeriesPromptStudioForm', (serverData = {}) => {
        const { categoryFoundationDetailUrlTemplate = '', categories = [] } = serverData;

        return {
            categories,
            selectedCategoryUuid: '',
            loadingFoundation: false,

            selectedCategory() {
                return this.categories.find(cat => cat.uuid === this.selectedCategoryUuid) ?? null;
            },

            /** Chỉ để HIỂN THỊ xem chuyên mục đã có ngữ cảnh biên tập chưa — nội dung thật được
             * BuildSeriesArchitecturePromptAction đọc lại từ DB ở server lúc submit form, không
             * dùng dữ liệu fetch ở đây để build prompt (tránh lệch pha client/server). */
            async applyCategoryFoundation() {
                const categoryUuid = this.selectedCategoryUuid;
                if (!categoryUuid) return;

                this.loadingFoundation = true;

                try {
                    const res = await fetch(categoryFoundationDetailUrlTemplate.replace('__UUID__', categoryUuid), {
                        headers: { 'Accept': 'application/json' },
                    });
                    const data = await res.json().catch(() => ({}));

                    if (this.selectedCategoryUuid !== categoryUuid) return;

                    const target = this.categories.find(c => c.uuid === categoryUuid);
                    if (target) target.foundation = data.foundation ?? null;
                } catch (e) {
                    console.error('[video-series-prompt-studio] failed to load category foundation detail', e);
                } finally {
                    if (this.selectedCategoryUuid === categoryUuid) this.loadingFoundation = false;
                }
            },

            foundationSummary() {
                const foundation = this.selectedCategory()?.foundation;
                if (!foundation) return '';

                const parts = [];
                if (foundation.audience) parts.push(`Đối tượng: ${foundation.audience}`);
                if (foundation.product_service_docs) parts.push('Đã có tài liệu sản phẩm/dịch vụ trọng tâm');

                return parts.join(' — ');
            },
        };
    });
});
</script>
@endpush
