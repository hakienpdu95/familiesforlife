@extends('layouts.backend')
@section('title', 'Trích xuất nội dung bài viết')

@section('content')
<div x-data="coreIdeaExtractorPage({{ Js::from([
    'apiUrl' => route('backend.api.coreideaextractor.extract'),
]) }})">

    <div class="mb-5">
        <h1 class="text-2xl font-bold text-base-content">Trích xuất nội dung bài viết</h1>
        <p class="text-sm text-base-content/50 mt-0.5">
            Nhập URL 1 bài viết bất kỳ để lấy dữ liệu thô (tiêu đề, heading, nội dung chính...) dưới dạng JSON —
            công cụ nghiên cứu ý tưởng viết bài. Module này chỉ trích xuất, không tự sinh ý chính bằng AI.
        </p>
    </div>

    <div class="card bg-base-100 shadow-sm border border-base-200 mb-4">
        <div class="card-body py-4 px-5">
            <form @submit.prevent="submit()" class="flex flex-wrap gap-3 items-end">
                <div class="form-control flex-1 min-w-72">
                    <label class="label py-0.5"><span class="label-text text-xs font-medium">URL bài viết</span></label>
                    <input type="url" x-model="url" required placeholder="https://..."
                           class="input input-sm input-bordered w-full">
                </div>
                <div class="form-control flex-1 min-w-72">
                    <label class="label py-0.5">
                        <span class="label-text text-xs font-medium">Selector vùng nội dung chính (tùy chọn)</span>
                    </label>
                    <input type="text" x-model="contentSelector" placeholder=".detail-content, #main-content..."
                           class="input input-sm input-bordered w-full">
                </div>
                <button type="submit" class="btn btn-primary btn-sm gap-1.5" :disabled="loading">
                    <span x-show="!loading">Trích xuất</span>
                    <span x-show="loading" x-cloak>Đang xử lý...</span>
                </button>
            </form>
            <p class="text-xs text-base-content/40 mt-2">
                Chỉ định id hoặc class của khối chứa nội dung chính (VD <code>.detail-content</code>, <code>#main-content</code>,
                <code>div.article-body</code>) để lấy <code>main_content</code> chính xác hơn thuật toán tự động. Có thể liệt kê
                nhiều selector, phân tách bởi dấu phẩy — hệ thống thử lần lượt, dùng selector đầu tiên khớp. Bỏ trống để dùng
                thuật toán tự động nhận diện.
            </p>
            <p x-show="errorMessage" x-cloak class="text-error text-sm mt-2" x-text="errorMessage"></p>
        </div>
    </div>

    <div class="card bg-base-100 shadow-sm border border-base-200" x-show="result" x-cloak>
        <div class="card-body py-4 px-5">
            <div class="flex items-center justify-between gap-3 mb-3 flex-wrap">
                <div class="flex items-center gap-2">
                    <span class="text-sm font-medium">Độ tin cậy:</span>
                    <span class="badge badge-sm" :class="confidenceBadgeClass()" x-text="confidenceLabel()"></span>
                </div>
                <button type="button" class="btn btn-ghost btn-xs gap-1.5" @click="copyJson()">
                    <svg class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                              d="M8 16H6a2 2 0 01-2-2V6a2 2 0 012-2h8a2 2 0 012 2v2m-6 12h8a2 2 0 002-2v-8a2 2 0 00-2-2h-8a2 2 0 00-2 2v8a2 2 0 002 2z"/>
                    </svg>
                    <span x-text="copied ? 'Đã copy!' : 'Copy JSON'"></span>
                </button>
            </div>

            <p x-show="result && result.notes" x-cloak class="text-xs text-warning mb-3" x-text="result?.notes"></p>

            <pre class="bg-base-200 rounded-lg p-4 text-xs overflow-x-auto max-h-[70vh]" x-text="prettyJson()"></pre>
        </div>
    </div>

</div>
@endsection

@push('scripts')
<script>
document.addEventListener('alpine:init', () => {
    Alpine.data('coreIdeaExtractorPage', (serverData = {}) => {
        const { apiUrl = '' } = serverData;

        return {
            url: '',
            contentSelector: '',
            loading: false,
            result: null,
            errorMessage: '',
            copied: false,

            async submit() {
                this.loading = true;
                this.errorMessage = '';
                this.result = null;

                const csrf = document.querySelector('meta[name=csrf-token]')?.content ?? '';

                try {
                    const res = await fetch(apiUrl, {
                        method: 'POST',
                        headers: {
                            'Content-Type':     'application/json',
                            'X-CSRF-TOKEN':      csrf,
                            'X-Requested-With':  'XMLHttpRequest',
                            'Accept':            'application/json',
                        },
                        body: JSON.stringify({
                            url: this.url,
                            main_content_selector: this.contentSelector || null,
                        }),
                    });

                    const data = await res.json().catch(() => ({}));

                    if (!res.ok) {
                        this.errorMessage = data.message || data.errors?.url?.[0] || 'Có lỗi xảy ra, vui lòng thử lại.';
                        return;
                    }

                    this.result = data;
                } catch (e) {
                    console.error('[core-idea-extractor] request failed', e);
                    this.errorMessage = 'Lỗi kết nối. Vui lòng thử lại.';
                } finally {
                    this.loading = false;
                }
            },

            prettyJson() {
                return this.result ? JSON.stringify(this.result, null, 2) : '';
            },

            confidenceLabel() {
                return ({ high: 'Cao', medium: 'Trung bình', low: 'Thấp' })[this.result?.extraction_confidence] ?? '';
            },

            confidenceBadgeClass() {
                return ({ high: 'badge-success', medium: 'badge-warning', low: 'badge-error' })[this.result?.extraction_confidence] ?? 'badge-ghost';
            },

            async copyJson() {
                await navigator.clipboard.writeText(this.prettyJson());
                this.copied = true;
                setTimeout(() => { this.copied = false; }, 2000);
            },
        };
    });
});
</script>
@endpush
