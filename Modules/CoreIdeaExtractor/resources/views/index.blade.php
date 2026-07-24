@extends('layouts.backend')
@section('title', 'Trích xuất nội dung bài viết')

@section('content')
<div x-data="coreIdeaExtractorPage({{ Js::from([
    'apiUrl' => route('backend.api.coreideaextractor.extract'),
    'apiBatchUrl' => route('backend.api.coreideaextractor.extract-batch'),
    'maxUrls' => config('core_idea_extractor.batch.max_urls', 7),
]) }})">

    <div class="mb-5">
        <h1 class="text-2xl font-bold text-base-content">Trích xuất nội dung bài viết</h1>
        <p class="text-sm text-base-content/50 mt-0.5">
            Nhập tối đa <span x-text="maxUrls"></span> URL (mỗi dòng 1 URL) để lấy dữ liệu thô (tiêu đề, heading, nội dung
            chính...) của từng nguồn dưới dạng 1 JSON — công cụ nghiên cứu ý tưởng viết bài, copy JSON này dán thẳng vào
            chat AI (VD claude.ai) để nghiên cứu sâu hơn. Module này chỉ trích xuất, không tự sinh ý chính bằng AI.
        </p>
    </div>

    <div class="card bg-base-100 shadow-sm border border-base-200 mb-4">
        <div class="card-body py-4 px-5">
            <div class="tabs tabs-boxed tabs-xs w-fit mb-3">
                <button type="button" class="tab" :class="{ 'tab-active': mode === 'url' }" @click="mode = 'url'">Nhập URL</button>
                <button type="button" class="tab" :class="{ 'tab-active': mode === 'html' }" @click="mode = 'html'">Dán mã HTML</button>
            </div>

            <form @submit.prevent="submit()" class="flex flex-col gap-3">
                <div class="form-control" x-show="mode === 'url'">
                    <label class="label py-0.5">
                        <span class="label-text text-xs font-medium">URL bài viết (mỗi dòng 1 URL, tối đa <span x-text="maxUrls"></span>)</span>
                    </label>
                    <textarea x-model="urlsText" :required="mode === 'url'" rows="6"
                              placeholder="https://vidu.com/bai-1&#10;https://vidu.com/bai-2&#10;..."
                              class="textarea textarea-bordered textarea-sm w-full font-mono text-xs"></textarea>
                    <p class="text-xs text-base-content/40 mt-1" x-show="parsedUrlCount() > 0">
                        <span x-text="parsedUrlCount()"></span> URL hợp lệ (đã bỏ dòng trống/trùng).
                        <span class="text-warning" x-show="parsedUrlCount() > maxUrls" x-cloak>
                            Chỉ <span x-text="maxUrls"></span> URL đầu tiên sẽ được xử lý.
                        </span>
                    </p>
                </div>

                <div class="flex flex-wrap gap-3 items-end">
                    <div class="form-control flex-1 min-w-72" x-show="mode === 'url'">
                        <label class="label py-0.5"><span class="label-text text-xs font-medium">Từ khóa nghiên cứu (tùy chọn)</span></label>
                        <input type="text" x-model="topic" placeholder="VD: ăn dặm cho bé"
                               class="input input-sm input-bordered w-full">
                    </div>
                    <div class="form-control flex-1 min-w-72">
                        <label class="label py-0.5">
                            <span class="label-text text-xs font-medium">Selector vùng nội dung chính (tùy chọn)</span>
                        </label>
                        <input type="text" x-model="contentSelector" placeholder=".detail-content, #main-content..."
                               class="input input-sm input-bordered w-full">
                    </div>
                </div>

                <div class="form-control" x-show="mode === 'html'" x-cloak>
                    <label class="label py-0.5"><span class="label-text text-xs font-medium">Mã HTML</span></label>
                    <textarea x-model="html" :required="mode === 'html'" rows="8"
                              placeholder="Dán mã nguồn trang (View Source / Ctrl+U), hoặc chỉ đoạn HTML trong khối nội dung chính (VD &lt;div class=&quot;post__content&quot;&gt;...&lt;/div&gt;)..."
                              class="textarea textarea-bordered textarea-sm w-full font-mono text-xs"></textarea>
                    <p class="text-xs text-base-content/40 mt-1">
                        Dùng khi trang chặn crawl tự động (lỗi HTTP 403 — bot protection/WAF). Dán CẢ trang (View Source) để lấy đủ
                        title/meta/ngôn ngữ; nếu chỉ dán riêng 1 khối nội dung (VD <code>div.post__content</code>) thì vẫn lấy được
                        <code>main_content</code>/<code>headings</code> bình thường, nhưng title/meta_description/author sẽ trống vì
                        không có <code>&lt;head&gt;</code> trong đoạn dán.
                    </p>
                </div>

                <div>
                    <button type="submit" class="btn btn-primary btn-sm gap-1.5" :disabled="loading">
                        <span x-show="!loading">Trích xuất</span>
                        <span x-show="loading" x-cloak>Đang xử lý...</span>
                    </button>
                </div>
            </form>
            <p class="text-xs text-base-content/40 mt-2" x-show="mode === 'url'">
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
                <template x-if="!isBatchResult()">
                    <div class="flex items-center gap-2">
                        <span class="text-sm font-medium">Độ tin cậy:</span>
                        <span class="badge badge-sm" :class="confidenceBadgeClass()" x-text="confidenceLabel()"></span>
                    </div>
                </template>
                <template x-if="isBatchResult()">
                    <div class="flex items-center gap-2 text-xs text-base-content/60">
                        <span x-text="`${result.source_coverage.successful}/${result.source_coverage.total_requested} nguồn thành công`"></span>
                        <span x-show="result.source_coverage.blocked" class="badge badge-warning badge-xs" x-text="`${result.source_coverage.blocked} bị chặn`"></span>
                        <span x-show="result.source_coverage.failed" class="badge badge-error badge-xs" x-text="`${result.source_coverage.failed} lỗi`"></span>
                    </div>
                </template>
                <button type="button" class="btn btn-ghost btn-xs gap-1.5" @click="copyJson()">
                    <svg class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                              d="M8 16H6a2 2 0 01-2-2V6a2 2 0 012-2h8a2 2 0 012 2v2m-6 12h8a2 2 0 002-2v-8a2 2 0 00-2-2h-8a2 2 0 00-2 2v8a2 2 0 002 2z"/>
                    </svg>
                    <span x-text="copied ? 'Đã copy!' : 'Copy JSON'"></span>
                </button>
            </div>

            <template x-if="isBatchResult()">
                <div class="flex flex-wrap gap-1.5 mb-3">
                    <template x-for="source in result.sources" :key="source.source_url">
                        <span class="badge badge-sm gap-1" :class="sourceBadgeClass(source.status)" :title="source.source_url">
                            <span x-text="source.domain"></span>
                            <span x-show="source.status !== 'success'" x-text="`(${source.block_reason ?? source.status})`"></span>
                        </span>
                    </template>
                </div>
            </template>

            <p x-show="!isBatchResult() && result && result.notes" x-cloak class="text-xs text-warning mb-3" x-text="result?.notes"></p>

            <pre class="bg-base-200 rounded-lg p-4 text-xs overflow-x-auto max-h-[70vh]" x-text="prettyJson()"></pre>
        </div>
    </div>

</div>
@endsection

@push('scripts')
<script>
document.addEventListener('alpine:init', () => {
    Alpine.data('coreIdeaExtractorPage', (serverData = {}) => {
        const { apiUrl = '', apiBatchUrl = '', maxUrls = 7 } = serverData;

        return {
            mode: 'url',
            urlsText: '',
            topic: '',
            html: '',
            contentSelector: '',
            loading: false,
            result: null,
            errorMessage: '',
            copied: false,
            maxUrls,

            parsedUrls() {
                return [...new Set(
                    this.urlsText.split('\n').map(u => u.trim()).filter(Boolean)
                )];
            },

            parsedUrlCount() {
                return this.parsedUrls().length;
            },

            async submit() {
                this.loading = true;
                this.errorMessage = '';
                this.result = null;

                const csrf = document.querySelector('meta[name=csrf-token]')?.content ?? '';
                const headers = {
                    'Content-Type':     'application/json',
                    'X-CSRF-TOKEN':      csrf,
                    'X-Requested-With':  'XMLHttpRequest',
                    'Accept':            'application/json',
                };

                const isBatch = this.mode === 'url';
                const endpoint = isBatch ? apiBatchUrl : apiUrl;
                const body = isBatch
                    ? JSON.stringify({
                        urls: this.parsedUrls().slice(0, this.maxUrls),
                        topic: this.topic || null,
                        main_content_selector: this.contentSelector || null,
                    })
                    : JSON.stringify({
                        url: null,
                        html: this.html,
                        main_content_selector: this.contentSelector || null,
                    });

                try {
                    const res = await fetch(endpoint, { method: 'POST', headers, body });
                    const data = await res.json().catch(() => ({}));

                    if (!res.ok) {
                        this.errorMessage = data.message
                            || data.errors?.urls?.[0]
                            || data.errors?.html?.[0]
                            || 'Có lỗi xảy ra, vui lòng thử lại.';
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

            isBatchResult() {
                return !!this.result?.sources;
            },

            prettyJson() {
                return this.result ? JSON.stringify(this.result, null, 2) : '';
            },

            confidenceLabel() {
                return ({ high: 'Cao', medium: 'Trung bình', low: 'Thấp' })[this.result?.extraction_confidence] ?? '';
            },

            sourceBadgeClass(status) {
                return ({ success: 'badge-success', blocked: 'badge-warning', error: 'badge-error' })[status] ?? 'badge-ghost';
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
