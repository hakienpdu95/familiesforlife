@extends('layouts.backend')
@section('title', 'Trích xuất nội dung bài viết')

@section('content')
<div x-data="coreIdeaExtractorPage({{ Js::from([
    'apiUrl' => route('backend.api.coreideaextractor.extract'),
    'apiBatchUrl' => route('backend.api.coreideaextractor.extract-batch'),
    'maxUrls' => config('core_idea_extractor.batch.max_urls', 7),
    'categoryFoundationsUrl' => route('backend.coreideaextractor.category-foundations.index'),
    'existingArticlesUrlTemplate' => route('backend.api.coreideaextractor.category-foundations.existing-articles', ['category' => '__UUID__']),
    'categories' => $categoryFoundations,
]) }})">

    <div class="mb-5 flex items-start justify-between flex-wrap gap-2">
        <div>
            <h1 class="text-2xl font-bold text-base-content">Trích xuất nội dung bài viết</h1>
            <p class="text-sm text-base-content/50 mt-0.5">
                Nhập tối đa <span x-text="maxUrls"></span> URL (mỗi dòng 1 URL) để lấy dữ liệu thô (tiêu đề, heading, nội dung
                chính...) của từng nguồn dưới dạng 1 JSON — công cụ nghiên cứu ý tưởng viết bài, copy JSON này dán thẳng vào
                chat AI (VD claude.ai) để nghiên cứu sâu hơn. Module này chỉ trích xuất, không tự sinh ý chính bằng AI.
            </p>
        </div>
        <a :href="categoryFoundationsUrl" class="btn btn-ghost btn-xs">Quản lý Content Foundation theo chuyên mục</a>
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

                <div class="form-control" x-show="mode === 'url' && parsedUrlCount() > 0" x-cloak>
                    <label class="label py-0.5">
                        <span class="label-text text-xs font-medium">Selector riêng cho từng URL (tùy chọn)</span>
                    </label>
                    <div class="flex flex-col gap-1 max-h-40 overflow-y-auto border border-base-200 rounded-lg p-2">
                        <template x-for="src in parsedSources().slice(0, maxUrls)" :key="src.url">
                            <div class="flex items-center gap-2">
                                <span class="text-xs text-base-content/60 truncate flex-1" x-text="src.url" :title="src.url"></span>
                                <input type="text" x-model="selectorOverrides[src.url]" placeholder="mặc định / tự động"
                                       class="input input-xs input-bordered w-44 font-mono">
                            </div>
                        </template>
                    </div>
                    <p class="text-xs text-base-content/40 mt-1">
                        Để trống 1 dòng = dùng selector mặc định bên dưới (hoặc tự động nếu selector mặc định cũng bỏ trống). Mỗi
                        nguồn thường thuộc domain/bố cục khác nhau nên selector hiếm khi giống nhau giữa các URL.
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
                            <span class="label-text text-xs font-medium" x-text="mode === 'url' ? 'Selector mặc định (tùy chọn)' : 'Selector vùng nội dung chính (tùy chọn)'"></span>
                        </label>
                        <input type="text" x-model="contentSelector" placeholder=".detail-content, #main-content..."
                               class="input input-sm input-bordered w-full">
                    </div>
                    <div class="form-control min-w-40">
                        <label class="label py-0.5">
                            <span class="label-text text-xs font-medium">Ngôn ngữ nguồn</span>
                        </label>
                        <select x-model="sourceLanguage" class="select select-sm select-bordered w-full">
                            <option value="vi">Tiếng Việt</option>
                            <option value="en">Tiếng Anh</option>
                            <option value="th">Tiếng Thái</option>
                            <option value="id">Tiếng Indonesia</option>
                        </select>
                    </div>
                </div>
                <p class="text-xs text-base-content/40 -mt-2">
                    Chọn tay ngôn ngữ thật của nguồn thay vì để hệ thống tự nhận diện qua <code>&lt;html lang&gt;</code>/ký tự nội dung — cách tự động nhiều khi không chính xác (site khai sai, hoặc không đủ tín hiệu để đối chiếu).
                </p>

                <details class="rounded-lg border border-base-200 px-3 py-2" x-show="mode === 'url'" open>
                    <summary class="cursor-pointer text-xs font-medium text-base-content/60">
                        Ngữ cảnh cho người viết (tùy chọn) — giúp AI không trả lời chung chung khi bạn dán JSON vào chat
                    </summary>

                    <div class="form-control mt-2">
                        <label class="label py-0.5">
                            <span class="label-text text-xs font-medium">Chuyên mục (tự nạp Content Foundation đã lưu, nếu có)</span>
                        </label>
                        <select x-model="selectedCategoryUuid" @change="applyCategoryFoundation()" class="select select-sm select-bordered w-full">
                            <option value="">— Không chọn —</option>
                            <template x-for="cat in categories" :key="cat.uuid">
                                <option :value="cat.uuid" x-text="'—'.repeat(cat.depth) + ' ' + cat.name"></option>
                            </template>
                        </select>
                        <p x-show="selectedFoundationSummary()" x-cloak class="text-xs text-base-content/40 mt-1" x-text="selectedFoundationSummary()"></p>
                        <p x-show="selectedCategoryUuid && loadingExistingArticles" x-cloak class="text-xs text-base-content/40 mt-1">Đang tải danh sách bài đã có trong chuyên mục...</p>
                        <p x-show="selectedCategoryUuid && !loadingExistingArticles && existingArticleTitles.length" x-cloak class="text-xs text-base-content/40 mt-1">
                            <span x-text="existingArticleTitles.length"></span> bài đã publish trong chuyên mục này sẽ được đưa vào prompt để AI tránh gợi ý trùng.
                        </p>
                    </div>

                    <div class="flex flex-wrap gap-3 mt-2">
                        <div class="form-control flex-1 min-w-64">
                            <label class="label py-0.5"><span class="label-text text-xs font-medium">Đối tượng độc giả</span></label>
                            <input type="text" x-model="audience" placeholder="VD: mẹ mới sinh con đầu lòng, chưa có kinh nghiệm"
                                   class="input input-sm input-bordered w-full">
                        </div>
                        <div class="form-control flex-1 min-w-64">
                            <label class="label py-0.5"><span class="label-text text-xs font-medium">Mục tiêu bài viết</span></label>
                            <input type="text" x-model="goal" placeholder="VD: bài blog 1500 từ, cần góc nhìn khác các nguồn tham khảo"
                                   class="input input-sm input-bordered w-full">
                        </div>
                        <div class="form-control flex-1 min-w-64">
                            <label class="label py-0.5"><span class="label-text text-xs font-medium">Ràng buộc / không muốn</span></label>
                            <input type="text" x-model="constraints" placeholder="VD: không viết giọng hàn lâm, không quảng cáo sản phẩm"
                                   class="input input-sm input-bordered w-full">
                        </div>
                    </div>
                    <div class="form-control mt-2">
                        <label class="label py-0.5">
                            <span class="label-text text-xs font-medium">Đoạn văn mẫu (giọng văn của bạn — ví dụ thật hiệu quả hơn mô tả)</span>
                        </label>
                        <textarea x-model="styleSample" rows="3" placeholder="Dán 1 đoạn bạn từng viết để AI học theo giọng văn thật, thay vì chỉ mô tả bằng lời..."
                                  class="textarea textarea-bordered textarea-sm w-full text-xs"></textarea>
                    </div>
                </details>

                <label class="label cursor-pointer justify-start gap-2 py-0" x-show="mode === 'url'">
                    <input type="checkbox" x-model="forceRefresh" class="checkbox checkbox-xs">
                    <span class="label-text text-xs text-base-content/60">
                        Bỏ qua cache, fetch lại từ đầu (HTML mỗi URL được cache {{ (int) (config('core_idea_extractor.cache.fetch_ttl_seconds', 3600) / 60) }} phút — tick nếu nghi ngờ nội dung trang đã đổi hoặc site đã hết bị chặn)
                    </span>
                </label>

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
                <code>div.article-body</code>) để lấy <code>main_content</code> chính xác hơn thuật toán tự động. Có thể ghép
                nhiều class/id liền nhau (không dấu cách) để khớp ĐÚNG 1 khối có ĐỦ tất cả class đó — VD
                <code>.col-md-12.content-full</code> cho <code>&lt;div class="col-md-12 content-full"&gt;</code>, hữu ích khi 1
                class riêng lẻ (VD chỉ <code>.content-full</code>) khớp nhầm cả khối khác trên trang (VD sidebar cũng gắn class
                đó). Có thể liệt kê nhiều selector khác nhau, phân tách bởi dấu phẩy — hệ thống thử lần lượt, dùng selector đầu
                tiên khớp. Bỏ trống để dùng thuật toán tự động nhận diện.
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
                        <span x-text="`${result.source_coverage.success}/${result.requested_count} nguồn thành công`"></span>
                        <span x-show="result.source_coverage.blocked" class="badge badge-warning badge-xs" x-text="`${result.source_coverage.blocked} bị chặn`"></span>
                        <span x-show="result.source_coverage.error" class="badge badge-error badge-xs" x-text="`${result.source_coverage.error} lỗi`"></span>
                    </div>
                </template>
                <div class="flex items-center gap-1.5">
                    <button type="button" class="btn btn-ghost btn-xs gap-1.5" @click="copyPromptForAi()">
                        <svg class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                  d="M8 10h.01M12 10h.01M16 10h.01M9 16H5a2 2 0 01-2-2V6a2 2 0 012-2h14a2 2 0 012 2v8a2 2 0 01-2 2h-6l-4 4v-4z"/>
                        </svg>
                        <span x-text="copiedPrompt ? 'Đã copy!' : 'Copy prompt cho AI'"></span>
                    </button>
                    <button type="button" class="btn btn-ghost btn-xs gap-1.5" @click="copyJson()">
                        <svg class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                  d="M8 16H6a2 2 0 01-2-2V6a2 2 0 012-2h8a2 2 0 012 2v2m-6 12h8a2 2 0 002-2v-8a2 2 0 00-2-2h-8a2 2 0 00-2 2v8a2 2 0 002 2z"/>
                        </svg>
                        <span x-text="copied ? 'Đã copy!' : 'Copy JSON'"></span>
                    </button>
                </div>
            </div>

            <template x-if="isBatchResult()">
                <div class="flex flex-wrap gap-1.5 mb-3">
                    <template x-for="source in result.sources" :key="source.url">
                        <span class="badge badge-sm gap-1" :class="sourceBadgeClass(source.status)"
                              :title="source.duplicate_of ? `Trùng nội dung với: ${source.duplicate_of}` : source.url">
                            <span x-text="source.domain"></span>
                            <span x-show="source.status !== 'success'" x-text="`(${source.failure_type ?? source.status})`"></span>
                            <span x-show="source.duplicate_of" x-cloak class="opacity-70">(trùng)</span>
                        </span>
                    </template>
                </div>
            </template>

            <p x-show="!isBatchResult() && result && result.notes" x-cloak class="text-xs text-warning mb-3" x-text="result?.notes"></p>
            <p x-show="isBatchResult() && result.summary_note" x-cloak class="text-xs text-warning mb-3" x-text="result?.summary_note"></p>
            <p x-show="isPromptLarge()" x-cloak class="text-xs text-warning mb-3" x-text="promptSizeWarningText()"></p>

            <pre class="bg-base-200 rounded-lg p-4 text-xs overflow-x-auto max-h-[70vh]" x-text="prettyJson()"></pre>
        </div>
    </div>

</div>
@endsection

@push('scripts')
<script>
document.addEventListener('alpine:init', () => {
    Alpine.data('coreIdeaExtractorPage', (serverData = {}) => {
        const { apiUrl = '', apiBatchUrl = '', maxUrls = 7, categoryFoundationsUrl = '', existingArticlesUrlTemplate = '', categories = [] } = serverData;

        return {
            mode: 'url',
            urlsText: '',
            topic: '',
            audience: '',
            goal: '',
            constraints: '',
            styleSample: '',
            html: '',
            contentSelector: '',
            /**
             * Selector RIÊNG theo từng URL — key là chính URL (không phải index), value là chuỗi
             * selector người dùng gõ trong ô tương ứng ở danh sách "Selector riêng cho từng URL".
             * Dùng URL làm key (không phải vị trí dòng trong textarea) để override KHÔNG bị lệch
             * khi người dùng thêm/xoá/sắp xếp lại dòng — sửa URL khác không ảnh hưởng override
             * đã gõ cho URL này, và override vẫn còn nguyên nếu người dùng tạm xoá rồi dán lại
             * đúng URL đó.
             */
            selectorOverrides: {},
            sourceLanguage: 'vi',
            forceRefresh: false,
            loading: false,
            result: null,
            errorMessage: '',
            copied: false,
            copiedPrompt: false,
            maxUrls,
            categoryFoundationsUrl,
            existingArticlesUrlTemplate,
            categories,
            selectedCategoryUuid: '',
            existingArticleTitles: [],
            loadingExistingArticles: false,

            parsedUrls() {
                return [...new Set(
                    this.urlsText.split('\n').map(u => u.trim()).filter(Boolean)
                )];
            },

            parsedUrlCount() {
                return this.parsedUrls().length;
            },

            /**
             * Ghép mỗi URL với selector RIÊNG người dùng gõ ở danh sách "Selector riêng cho từng
             * URL" (`selectorOverrides`, key theo URL) — nhiều nguồn trong 1 batch thường thuộc
             * nhiều domain/bố cục khác nhau, 1 selector mặc định chung (`contentSelector`) hiếm
             * khi đúng cho tất cả. Ô để trống → `selector: null` → submit() rơi về
             * `main_content_selector` chung, rồi tới tự động — xem
             * CoreIdeaExtractorController::resolveSelectorForUrl().
             */
            parsedSources() {
                return this.parsedUrls().map(url => ({
                    url,
                    selector: (this.selectorOverrides[url] || '').trim() || null,
                }));
            },

            selectedCategory() {
                return this.categories.find(cat => cat.uuid === this.selectedCategoryUuid) ?? null;
            },

            /**
             * Prefill CÁC field ad-hoc hiện có (audience/goal/constraints/styleSample) từ
             * Category Content Foundation đã lưu — vẫn để người dùng tự sửa tiếp cho lần chạy
             * này, không khoá field (spec/CoreIdeaExtractor.md §12, v1.4). Đồng thời fetch danh
             * sách bài đã publish trong category (§12.8, v1.11) — RESET trước, không chỉ khi có
             * foundation, để không giữ lại danh sách của category đã chọn TRƯỚC ĐÓ khi người dùng
             * đổi sang category khác/bỏ chọn.
             */
            applyCategoryFoundation() {
                const category = this.selectedCategory();
                this.existingArticleTitles = [];

                if (!category) return;

                const foundation = category.foundation;
                if (foundation) {
                    this.audience = foundation.audience || this.audience;
                    this.goal = foundation.content_goals || this.goal;
                    this.constraints = foundation.constraints || this.constraints;
                    this.styleSample = foundation.style_sample || this.styleSample;
                }

                this.fetchExistingArticles(category.uuid);
            },

            async fetchExistingArticles(categoryUuid) {
                this.loadingExistingArticles = true;

                try {
                    const res = await fetch(this.existingArticlesUrlTemplate.replace('__UUID__', categoryUuid), {
                        headers: { 'Accept': 'application/json' },
                    });
                    const data = await res.json().catch(() => ({}));

                    this.existingArticleTitles = data.titles || [];
                } catch (e) {
                    console.error('[core-idea-extractor] failed to load existing articles', e);
                    this.existingArticleTitles = [];
                } finally {
                    this.loadingExistingArticles = false;
                }
            },

            selectedFoundationSummary() {
                const foundation = this.selectedCategory()?.foundation;
                if (!foundation) return '';

                const parts = [];
                if (foundation.core_focus) parts.push(`Trọng tâm: ${foundation.core_focus}`);
                if (foundation.unique_angle) parts.push(`Góc nhìn khác biệt: ${foundation.unique_angle}`);
                if (foundation.pain_points) parts.push(`Pain points: ${foundation.pain_points}`);

                return parts.join(' — ');
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
                const batchSources = isBatch ? this.parsedSources().slice(0, this.maxUrls) : [];
                const body = isBatch
                    ? JSON.stringify({
                        urls: batchSources.map(s => s.url),
                        topic: this.topic || null,
                        audience: this.audience || null,
                        goal: this.goal || null,
                        constraints: this.constraints || null,
                        style_sample: this.styleSample || null,
                        main_content_selector: this.contentSelector || null,
                        main_content_selectors: batchSources.map(s => s.selector),
                        force_refresh: this.forceRefresh,
                        source_language: this.sourceLanguage || null,
                    })
                    : JSON.stringify({
                        url: null,
                        html: this.html,
                        main_content_selector: this.contentSelector || null,
                        source_language: this.sourceLanguage || null,
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

            /**
             * Payload RÚT GỌN dành RIÊNG cho "Copy prompt cho AI" — khác mục đích với "Copy JSON"
             * (giữ NGUYÊN this.result cho debug/audit qua prettyJson(), KHÔNG đổi). Phản hồi thực
             * tế sau khi test với Claude/Grok: nhiều field kỹ thuật thuần (`final_url`/
             * `failure_type`/`http_status`/`content_hash`/`duplicate_of`/`fetched_at`/
             * `error_message`, `canonical_url`/`content_category`/`publish_date`/`date_modified`/
             * `author`) không giúp ích gì cho việc BRAINSTORM Ý TƯỞNG — chỉ tốn token khi dán vào
             * chat AI, không đổi giá trị của "Copy JSON"/API response (vẫn đủ mọi field cho debug/
             * audit — xem RawExtractionData::toApiArray()/BatchSourceResultData::toApiArray()).
             * Batch: nguồn `blocked`/`error` rút gọn còn `{url, domain, status, failure_type}` —
             * toàn bộ field kỹ thuật khác vốn đã `null` (xem §7.1.1 spec) nên giữ nguyên chỉ lặp
             * lại hàng chục `null` vô ích, `summary_note` cấp batch đã đủ giải thích lý do.
             *
             * `main_content` CHỈ bị cắt khi `extraction_confidence === 'low'` — CỐ Ý KHÔNG áp dụng
             * cho `medium`/`high` (xem lịch sử §12.4/§12.5 spec: đã thử cắt main_content rồi BỎ
             * NGAY vì phá mất chiều sâu nội dung, trong khi lợi ích chỉ là suy đoán). Khác biệt ở
             * đây: `low` là mức Layer 2 KHÔNG BAO GIỜ chạy tới (§4/§7 — nội dung được coi là chưa
             * đủ tin cậy để sinh ý ngay từ thiết kế ban đầu), nên với riêng payload dán vào AI, chỉ
             * giữ 1 đoạn ngắn để AI biết nguồn tồn tại/sơ lược nội dung, thay vì dán nguyên khối
             * main_content mà module tự đánh giá là không đủ tin cậy.
             */
            buildAiPayload() {
                const LOW_CONFIDENCE_MAIN_CONTENT_CHARS = 3000;

                const trimLowConfidenceContent = (mainContent, confidence) => {
                    if (confidence !== 'low' || !mainContent || mainContent.length <= LOW_CONFIDENCE_MAIN_CONTENT_CHARS) {
                        return mainContent;
                    }

                    const window = mainContent.slice(0, LOW_CONFIDENCE_MAIN_CONTENT_CHARS);
                    const lastSpace = window.lastIndexOf(' ');

                    return (lastSpace > LOW_CONFIDENCE_MAIN_CONTENT_CHARS * 0.7 ? window.slice(0, lastSpace) : window) + '…';
                };

                const pickCore = (source) => ({
                    title: source.title,
                    meta_description: source.meta_description,
                    declared_content_type: source.declared_content_type,
                    content_type_signal: source.content_type_signal,
                    keywords: source.keywords,
                    headings: source.headings,
                    language: source.language,
                    extraction_confidence: source.extraction_confidence,
                    notes: source.notes,
                    publisher_name: source.publisher_name,
                    source_structure: source.source_structure,
                    main_content: trimLowConfidenceContent(source.main_content, source.extraction_confidence),
                });

                if (!this.isBatchResult()) {
                    return pickCore(this.result);
                }

                return {
                    common_keywords: this.result.common_keywords ?? [],
                    summary_note: this.result.summary_note ?? null,
                    sources: (this.result.sources ?? []).map(s => s.status === 'success'
                        ? { url: s.url, domain: s.domain, ...pickCore(s) }
                        : { url: s.url, domain: s.domain, status: s.status, failure_type: s.failure_type }),
                };
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

            /**
             * Cảnh báo NHẸ (không chặn, không tự cắt nội dung — bài học từ lần thử rút gọn
             * main_content trước đó: cắt content phá mất chiều sâu, xem copyPromptForAi()) khi
             * payload lớn. Tham khảo https://blog.neosage.io/p/the-ai-application-layer-where-context
             * — độ chính xác AI giảm dần khi context dài + nhiệm vụ phức tạp, KHÔNG có "ngưỡng an
             * toàn" cụ thể (GPT-4o: 99.3% → 69.7% khi task phức tạp + context dài, theo bài viết)
             * — nên đây chỉ là gợi ý dựa trên số đo THẬT, để người dùng tự quyết (giảm số nguồn/
             * chạy theo đợt), KHÔNG phải ngưỡng cứng.
             *
             * Phản hồi thực tế từ người dùng (test thật với Claude/Grok, batch 7 nguồn) — payload
             * lớn không chỉ ảnh hưởng ĐỘ CHÍNH XÁC mà còn khiến AI TRẢ LỜI CHẬM RÕ RỆT (thời gian
             * chờ ~ hàm của số token input phải đọc + số token output phải sinh — batch 7 nguồn còn
             * yêu cầu sinh 20-25 ý tưởng + đánh giá 4 tiêu chí + tới 2 bảng, nên phần SINH OUTPUT
             * cũng góp phần không nhỏ, không chỉ riêng input dài). Bổ sung nhắc về TỐC ĐỘ vào cùng
             * cảnh báo này (thay vì chỉ nói về độ chính xác như trước) — cùng 1 giải pháp (giảm số
             * nguồn/chạy theo đợt) xử lý được cả 2 vấn đề, không cần cảnh báo riêng.
             *
             * Ước lượng token = ký_tự / 4 — xấp xỉ thô (tiếng Việt có dấu/không phân từ bằng
             * khoảng trắng có thể lệch so với tokenizer thật), CHỈ để người dùng có cảm nhận độ
             * lớn tương đối, không phải con số chính xác cho billing.
             */
            estimatedPromptChars() {
                return this.result ? this.prettyJson().length : 0;
            },

            isPromptLarge() {
                return this.estimatedPromptChars() > 50000;
            },

            promptSizeWarningText() {
                const chars = this.estimatedPromptChars();
                const tokens = Math.round(chars / 4);

                return `Dữ liệu khá lớn (~${chars.toLocaleString('vi-VN')} ký tự, ~${tokens.toLocaleString('vi-VN')} token ước tính). `
                    + `Ngữ cảnh càng dài + nhiệm vụ càng phức tạp, độ chính xác AI có thể càng giảm, và AI cũng có thể trả lời CHẬM hơn rõ rệt — `
                    + `nếu câu trả lời không ổn hoặc chờ quá lâu, thử giảm số nguồn (VD 3-4 URL thay vì 7) hoặc chạy theo từng đợt nhỏ hơn.`;
            },

            async copyJson() {
                await navigator.clipboard.writeText(this.prettyJson());
                this.copied = true;
                setTimeout(() => { this.copied = false; }, 2000);
            },

            /**
             * LẤY CẢM HỨNG (không sao chép 1:1) từ "context sandwich"
             * (https://www.mindstudio.ai/blog/context-sandwich-prompting-method-better-ai-results)
             * + context engineering (https://www.promptingguide.ai/guides/context-engineering-guide).
             * Bài gốc định nghĩa TOP=Context, MIDDLE=Task (yêu cầu cụ thể), BOTTOM=Criteria (định
             * dạng/tiêu chí output) — 3 lớp cho prompt ĐƠN GIẢN, không có khái niệm 1 khối bằng
             * chứng/dữ liệu retrieved lớn. Code này KHÁC: MIDDLE ở đây là EVIDENCE (JSON thô đã
             * trích xuất), còn Task+Criteria gộp chung ở BOTTOM — thích ứng riêng cho kịch bản có
             * khối dữ liệu khổng lồ (tới ~84.000 ký tự/batch) mà bài gốc không đề cập tới. Đặt
             * Task+Criteria ở BOTTOM (không phải ngay sau TOP như bài gốc gợi ý) có lý do RIÊNG đã
             * kiểm chứng thật (hiệu ứng recency lúc model bắt đầu sinh câu trả lời — xem giải thích
             * ngay dưới), không phải áp dụng máy móc thứ tự Context→Task→Criteria của bài gốc.
             *
             * TOP = vai trò + bối cảnh (foundation/ad-hoc, súc tích — "more context isn't always
             * better"), MIDDLE = JSON thô ĐẦY ĐỦ đã trích xuất (phần "filling") — KHÔNG rút gọn
             * main_content: đã thử cắt còn ~500 ký tự ở 1 bản trước nhưng phá mất chiều sâu nội
             * dung (500 ký tự chỉ đủ 1 đoạn mở bài) trong khi tool này TỒN TẠI để nghiên cứu sâu
             * nguồn — cái mất (nội dung thực chất) chắc chắn xảy ra, còn cái được (model "chú ý"
             * phần cuối tốt hơn) chỉ là suy đoán và không đáng với model hiện đại (context window
             * lớn, có ranh giới cấu trúc rõ ràng); server đã tự giới hạn kích thước rồi (single
             * 100.000 ký tự, batch 12.000 ký tự/nguồn — §core_idea_extractor.max_main_content_chars/
             * batch.max_main_content_chars_per_source), không cần thêm 1 lớp cắt nữa ở client.
             * BOTTOM = nhiệm vụ 3 bước + ĐỊNH DẠNG OUTPUT tường minh (2 bảng), đặt NGAY TRƯỚC chỗ
             * model bắt đầu sinh câu trả lời vì đó là vùng model "chú ý" nhiều nhất khi generate.
             * Output yêu cầu dạng bảng Markdown — kime.ai: bảng được trích dẫn nhiều gấp 4.2x so
             * với văn xuôi mô tả cùng dữ liệu. 3 tinh chỉnh dựa trên test thật với grok.com/claude.ai
             * (spec/CoreIdeaExtractor.md §12.4, v1.8):
             * (1) Thêm cột "Lý do" — trước đó mọi ý tưởng đều "Có" tuyệt đối ở cả 3 tiêu chí vì AI
             *     tự lọc TRƯỚC khi hiển thị (đúng theo yêu cầu "chỉ giữ lại ý tưởng thoả cả 3"),
             *     khiến cột Có/Không thành "con dấu" vô nghĩa, không verify được AI đang nghĩ gì.
             * (2) Thêm Bảng 2 liệt kê ý tưởng BỊ LOẠI kèm lý do — cho thấy bộ lọc thật sự hoạt
             *     động (không phải chỉ hiển thị ý tưởng đã được chọn sẵn), giúp đánh giá bộ lọc có
             *     đang quá lỏng/quá chặt.
             * (3) Khi có ≥2 nguồn thành công (batch), bắt buộc ít nhất 1 ý tưởng TỔNG HỢP CHÉO
             *     nhiều nguồn — dạng insight khó bị sao chép nhất vì không nguồn đơn lẻ nào tự có.
             * v1.11 (§12.7/§12.8) — tránh AI đề xuất lại ý tưởng đã có/đã bị từ chối, tham khảo
             * matthopkins.com (Decision Log) + memgraph.com (curation/entity resolution), bổ
             * sung cho nhau: `foundation.rejected_ideas` là tribal knowledge editor tự ghi tay
             * (KHÔNG suy ra được từ dữ liệu), `existingArticleTitles` là danh sách bài ĐÃ publish
             * trong category — tự động, khách quan, không cần ai nhớ cập nhật tay (xem
             * fetchExistingArticles()). Cả 2 đưa vào TOP + có chỉ dẫn tường minh ở BOTTOM (không
             * chỉ đưa context suông — context engineering: chỉ dẫn tường minh đáng tin hơn hy vọng
             * model tự suy ra từ context).
             * Không gọi AI Provider nào ở backend — giữ triết lý "công cụ nghiên cứu, copy tay"
             * hiện có.
             *
             * Bổ sung (chuẩn hoá JSON đầu ra cho prompt thủ công) — tận dụng field OpenGraph/
             * JSON-LD mới (canonical_url/content_category/declared_content_type/date_modified/
             * publisher_name, xem ExtractRawContentAction) do CHÍNH site khai báo thay vì suy đoán:
             * (1) Thêm câu chú giải ngắn ở đầu MIDDLE để AI không hiểu sai field mới/dễ nhầm
             *     (declared_content_type vs suy đoán, date_modified vs publish_date).
             * (2) Thêm chỉ dẫn tường minh dùng common_keywords (giao keywords các nguồn, tính bằng
             *     PHP thuần ở ExtractBatchResultData) làm điểm khởi đầu tổng hợp chéo — cùng
             *     nguyên tắc "chỉ dẫn tường minh đáng tin hơn hy vọng model tự suy ra".
             * (3) Thêm chỉ dẫn ưu tiên nguồn date_modified gần đây hơn khi các nguồn mâu thuẫn.
             * (4) Thêm lưu ý hạ độ tin cậy theo extraction_confidence/notes (paywall) — trước đó
             *     2 field này CÓ trong JSON nhưng chưa từng được nhắc tới ở BOTTOM nên dễ bị AI
             *     bỏ qua khi đánh giá.
             *
             * Bổ sung tiếp (đọc thêm subramanya.ai/2026/04/23/context-engineering-why-prompt-
             * engineering-was-never-enough + getcollate.io/learning-center/context-engineering):
             * (5) SỬA LỖI THẬT — TOP trước đó lấy audience/goal/constraints/styleSample từ state
             *     form hiện tại (this.audience...), còn JSON ở MIDDLE lại có result.brief (giá
             *     trị đã submit) + result.topic KHÔNG hề xuất hiện ở TOP dù người dùng có nhập.
             *     Nếu người dùng sửa ô input sau khi có kết quả rồi mới bấm "Copy prompt", TOP và
             *     JSON nêu 2 giá trị khác nhau cho CÙNG 1 field — đúng "context confusion" (thông
             *     tin mâu thuẫn nhau trong context) bài viết mô tả. Nay TOP đọc từ result.brief/
             *     result.topic (nguồn đã xử lý thật, single-URL fallback về state JS vì response
             *     không có brief/topic) — 1 nguồn sự thật duy nhất.
             * (6) Loại `brief`/`topic` khỏi JSON dán ở MIDDLE (đã có ở TOP) — tránh lặp lại cùng
             *     thông tin 2 lần trong 1 prompt, tăng "signal density" thay vì chỉ tăng số token.
             *     Nút "Copy JSON" riêng (prettyJson()) không đổi, vẫn xuất bản đầy đủ nguyên trạng.
             *
             * Phản hồi thực tế từ người dùng (test với 3 nguồn thật) — Bước 1 giới hạn "tối đa
             * 8-10 ý tưởng ứng viên" khiến sau khi lọc ở Bước 2 chỉ còn 3 ý đạt cả 3 tiêu chí ở
             * Bảng 1, quá ít để chọn:
             * (7) Tăng Bước 1 từ "tối đa 8-10" thành "20-25 ý tưởng, đa dạng góc nhìn" — kèm gợi ý
             *     cụ thể các dạng góc nhìn (theo giai đoạn/độ tuổi, theo đối tượng đặc thù, dạng
             *     so sánh, checklist, sai lầm thường gặp, FAQ) để tránh AI chỉ biến tấu lại vài ý
             *     giống nhau cho đủ số lượng — pool ứng viên rộng hơn mới có đủ ý SỐNG SÓT qua bộ
             *     lọc 3 tiêu chí nghiêm ngặt, thay vì chỉ tăng ngưỡng lọc lỏng hơn (sẽ hạ chất
             *     lượng Bảng 1).
             * (8) Thêm chỉ tiêu tường minh "Bảng 1 cần ÍT NHẤT 10 ý tưởng đạt cả 3 tiêu chí, nếu
             *     chưa đủ thì quay lại Bước 1 sinh thêm" — có "van an toàn" cho phép dừng dưới 10
             *     kèm giải thích nếu dữ liệu nguồn thực sự không đủ sâu, để AI không bịa ý tưởng
             *     yếu/generic chỉ để đủ số lượng (đánh đổi rõ ràng: ưu tiên trung thực về giới hạn
             *     dữ liệu hơn là ép đủ 10 ý bằng mọi giá).
             *
             * (9) Dùng field mới `content_type_signal` (rule-based, xem ExtractRawContentAction::
             *     classifyContentTypeSignal — listicle/how_to/review_comparison/faq suy từ pattern
             *     heading/tiêu đề, KHÔNG phải AI): thêm chú giải rõ đây là PHỎNG ĐOÁN có thể sai
             *     (khác các field OpenGraph/JSON-LD do site tự khai ở mục (1)-(4), đáng tin hơn);
             *     và khi ĐA SỐ nguồn batch cùng chung 1 content_type_signal, gợi ý chọn định dạng
             *     bài KHÁC — cùng tinh thần appendStructureNote() ở controller (tránh lặp lại hình
             *     thức nội dung đã phổ biến ở các nguồn tham khảo).
             *
             * (10) "Lost in the middle" (machinelearningmastery.com/context-vs-memory-engineering-
             *      in-agentic-ai-systems: model chú ý mạnh ở ĐẦU/CUỐI context, phần GIỮA — chính là
             *      MIDDLE (JSON thô, có thể tới ~84.000 ký tự với batch 7 nguồn) — bị "lãng quên"
             *      nhiều nhất) — 2 tiêu chí đánh giá ở BƯỚC 2 cần dùng ĐÚNG core_focus/goal đã nêu ở
             *      TOP, nhưng TOP nằm cách BƯỚC 2 cả khối MIDDLE khổng lồ. Trước đây BƯỚC 2 chỉ nhắc
             *      TÊN tiêu chí ("trọng tâm nội dung của chuyên mục NÀY", "mục tiêu đã nêu Ở TRÊN")
             *      mà không nhắc lại GIÁ TRỊ, buộc model phải tự nhớ lại nội dung TOP xuyên qua toàn
             *      bộ MIDDLE — rủi ro y hệt lý do khiến (2)/(7) đã inline common_keywords/
             *      dominantSignal thẳng vào BOTTOM thay vì chỉ trỏ tên field. Áp dụng NHẤT QUÁN
             *      cùng nguyên tắc đó cho tiêu chí 1/3: khi có core_focus/goal, nhắc lại GIÁ TRỊ
             *      thật ngay trong câu tiêu chí (coreFocusText/goalText bên dưới) — CHỈ 2 giá trị
             *      ngắn (core_focus/goal), không phải lặp cả khối `brief`/`foundation` như (6) đã
             *      cố tình tránh, nên không đổi quyết định "signal density" đã có, chỉ neo đúng 2
             *      giá trị ĐANG được dùng làm tiêu chí đánh giá vào đúng chỗ áp dụng.
             *
             * (11) Thêm tiêu chí 4 "Phù hợp đối tượng độc giả" — trong 4 field ngữ cảnh riêng đã có
             *      ở TOP (audience/goal/constraints/style_sample), chỉ `goal` được dùng làm tiêu chí
             *      chọn Ý TƯỞNG (tiêu chí 3); `audience` bị bỏ sót hoàn toàn dù cùng mức quan trọng —
             *      1 ý tưởng có thể khớp trọng tâm + độc quyền + đúng mục tiêu nhưng vẫn sai độ phức
             *      tạp/giọng văn so với đối tượng độc giả cụ thể (VD audience khai "chưa có kinh
             *      nghiệm" nhưng ý tưởng giả định kiến thức nền), mà không tiêu chí nào trước đó bắt
             *      được lỗi này. KHÔNG đưa `constraints`/`style_sample` vào tiêu chí — 2 field này là
             *      ràng buộc VỀ CÁCH VIẾT (giọng văn, không quảng cáo...), khác phạm vi với 4 tiêu
             *      chí đều là chọn Ý TƯỞNG nào đáng viết; gộp nhầm sẽ làm tiêu chí mất rõ ràng. Tiêu
             *      chí 4 (và tiêu chí 2 — trước đó thiếu neo giá trị `unique_angle` dù cùng dạng với
             *      1/3) đều neo giá trị thật ngay tại câu tiêu chí, nhất quán với (10). Đồng bộ mọi
             *      chỗ giả định "3 tiêu chí" trước đó (header Bước 2, tên cột + tiêu đề Bảng 1, câu
             *      "Mục tiêu số lượng") sang "4 tiêu chí" — trừ các đoạn trong docblock lịch sử (1)/
             *      (7)/(8) ở trên vẫn giữ nguyên "3" vì mô tả ĐÚNG bối cảnh tại thời điểm đó.
             *
             * (12) Câu vai trò (persona) mở đầu TOP trước đây CỐ ĐỊNH "biên tập viên giàu kinh
             *      nghiệm" + tên category (nếu có), hoàn toàn không đổi theo chủ đề/đối tượng độc
             *      giả cụ thể của từng lần chạy — phản hồi từ người dùng: câu này "hơi cố định,
             *      không linh hoạt theo context, chủ đề, đối tượng độc giả". Dệt thẳng `promptTopic`/
             *      `audienceText` vào câu vai trò (xem chi tiết ngay phía dưới, trước khai báo
             *      `top`) thay vì để 2 dòng ngữ cảnh đứng riêng ngay sau — role-based prompting
             *      (gsdcouncil.org/iternal.ai) cho kết quả bám sát chuyên môn/đối tượng hơn khi vai
             *      trò gắn liền ngữ cảnh cụ thể thay vì vai trò chung chung kèm dữ kiện rời rạc bên
             *      cạnh. Không có topic/audience → rơi về đúng câu cũ (không đổi hành vi mặc định).
             *
             * (13) "Derivability Test" (bosio.digital/articles/context-engineering-rules — Anthropic):
             *      GIỮ 1 chỉ dẫn nếu model KHÔNG thể tự suy ra được từ ngữ cảnh xung quanh VÀ sai sót
             *      là "âm thầm mà tốn kém" (silent and costly). Soát lại toàn bộ TOP/BOTTOM hiện có
             *      theo tiêu chí này — không tìm thấy chỉ dẫn nào đủ thừa để xoá (phần lớn đã được
             *      thêm sau khi quan sát THẬT model suy luận sai nếu thiếu, xem lịch sử (1)-(12)).
             *      Nhưng lộ 1 khoảng trống thật: `familiesforlife.com` có 2 chuyên mục lõi "Sức khoẻ
             *      gia đình"/"Dinh dưỡng" — 1 ý tưởng SAI ở nhóm chủ đề này (mẹo dân gian/claim y
             *      khoa chưa kiểm chứng) là rủi ro "silent and costly" thật (model không có cách nào
             *      tự biết ranh giới này nếu không nói rõ, hậu quả ảnh hưởng sức khoẻ độc giả, nặng
             *      hơn hẳn 1 ý tưởng dở thông thường) — mà BƯỚC 1 trước đó hoàn toàn không nhắc tới.
             *      Thêm 1 câu ràng buộc NGAY TRONG BƯỚC 1 (chỗ sinh ý tưởng, trước khi ý tưởng "lỡ"
             *      được đề xuất) thay vì chỉ lọc ở BƯỚC 2 — chặn từ gốc rẻ hơn lọc sau. Luôn bật
             *      (không điều kiện theo category) vì chủ đề sức khoẻ/an toàn trẻ em có thể xuất
             *      hiện ở nhiều chuyên mục khác ngoài "Dinh dưỡng"/"Sức khoẻ gia đình" (VD "Nuôi dạy
             *      con"/"Kỹ năng sống"), match theo tên category sẽ không đủ tin cậy.
             *
             * (14) Đối chiếu quy tắc "Avoid redundancy; don't restate constraints multiple times"
             *      của chính bài context-sandwich (xem đầu hàm) với việc neo lại core_focus/goal/
             *      unique_angle/audience ở CẢ TOP lẫn BOTTOM (mục (10)/(11)) — KHÔNG mâu thuẫn: quy
             *      tắc đó cảnh báo kiểu lặp LIỀN KỀ vô ích (nói 2 lần ngay cạnh nhau, không thêm giá
             *      trị — đúng lý do (6) đã bỏ dòng "Từ khóa nghiên cứu"/"Đối tượng độc giả" đứng
             *      riêng ở TOP), khác hẳn việc neo giá trị CÓ CHỦ ĐÍCH qua khoảng cách xa (cách nhau
             *      cả khối MIDDLE ~84.000 ký tự) để chống "lost in the middle" — 2 tình huống khác
             *      bản chất dù cùng là "nhắc lại 1 giá trị hơn 1 lần trong prompt".
             */
            async copyPromptForAi() {
                if (!this.result) return;

                const category = this.selectedCategory();
                const foundation = category?.foundation;
                const successfulSourceCount = this.isBatchResult()
                    ? (this.result.sources ?? []).filter(s => s.status === 'success').length
                    : 1;

                /**
                 * `language` (§ExtractRawContentAction::extractLanguage, đọc từ `<html lang="...">`)
                 * có sẵn trên mỗi nguồn nhưng trước đây chưa từng được dùng ở BOTTOM — nếu nguồn
                 * tham khảo (VD tiếng Anh) chiếm phần lớn MIDDLE (tới ~84.000 ký tự/batch), trong khi
                 * TOP/BOTTOM chỉ vài trăm ký tự tiếng Việt, AI có thể ngầm lệch sang trả lời/đặt tiêu
                 * đề bằng ngôn ngữ nguồn hoặc dịch máy móc nguyên văn — cùng rủi ro "chỉ dẫn tường
                 * minh đáng tin hơn hy vọng model tự suy ra" đã áp dụng ở (2)/(9)/(10)/(11), nên thêm
                 * chỉ dẫn NGÔN NGỮ OUTPUT tường minh thay vì dựa vào suy đoán ngầm.
                 */
                const sourceLanguages = this.isBatchResult()
                    ? new Set((this.result.sources ?? [])
                        .filter(s => s.status === 'success' && s.language && s.language !== 'unknown')
                        .map(s => s.language))
                    : new Set(this.result.language && this.result.language !== 'unknown' ? [this.result.language] : []);
                const hasNonVietnameseSource = [...sourceLanguages].some(lang => lang !== 'vi');

                // Dùng giá trị ĐÃ THỰC SỰ được xử lý (this.result.brief/topic — chỉ có ở batch
                // mode) thay vì state form hiện tại (this.audience/goal/...) — nếu người dùng sửa
                // ô input SAU khi đã có kết quả nhưng TRƯỚC khi bấm "Copy prompt", dùng state hiện
                // tại sẽ khiến TOP và JSON (result.brief) nêu 2 giá trị KHÁC NHAU cho cùng 1 field,
                // model đọc phải 2 nguồn mâu thuẫn ("context confusion" — subramanya.ai/2026/04/23/
                // context-engineering-why-prompt-engineering-was-never-enough). Single-URL mode
                // không có `brief`/`topic` trong response nên fallback về state JS hiện tại.
                const brief = this.result.brief ?? {
                    audience: this.audience || null,
                    goal: this.goal || null,
                    constraints: this.constraints || null,
                    style_sample: this.styleSample || null,
                };
                const promptTopic = this.isBatchResult() ? (this.result.topic ?? null) : (this.topic || null);
                const coreFocusText = foundation?.core_focus || null;
                const uniqueAngleText = foundation?.unique_angle || null;
                const goalText = brief.goal || null;
                const audienceText = brief.audience || null;

                /**
                 * Persona (PCRF — iternal.ai/gsdcouncil: role-based prompting bám chuyên môn CỤ THỂ
                 * cho kết quả đúng trọng tâm hơn hẳn 1 vai trò chung chung) — trước đây câu vai trò
                 * CỐ ĐỊNH "biên tập viên giàu kinh nghiệm" bất kể chủ đề/đối tượng độc giả cụ thể ra
                 * sao, chỉ đổi theo category đã chọn; `promptTopic`/`audienceText` lại nằm tách rời
                 * thành 2 dòng ngữ cảnh phía dưới, không gắn liền với vai trò. Nay dệt thẳng 2 giá
                 * trị này vào câu vai trò — role-based prompting hiệu quả hơn khi vai trò gắn với
                 * NGỮ CẢNH CỤ THỂ (nghiên cứu về CÁI GÌ, viết CHO AI đọc) thay vì chỉ liệt kê dữ kiện
                 * bên cạnh 1 vai trò chung chung y hệt mọi lần chạy. Bỏ 2 dòng "Từ khóa nghiên cứu"/
                 * "Đối tượng độc giả" đứng riêng ngay bên dưới vì cùng giá trị đã dệt vào câu vai trò
                 * — giữ cả 2 sẽ lặp lại đúng 1 thông tin 2 lần liền kề nhau trong TOP, giảm signal
                 * density (cùng nguyên tắc đã áp dụng ở (6); KHÔNG mâu thuẫn với việc audienceText
                 * còn được neo lại ở tiêu chí 4 BOTTOM — đó là lặp lại có chủ đích qua khoảng cách xa
                 * (chống "lost in the middle", xem (10)/(11)), khác với lặp liền kề vô ích ở đây).
                 */
                const personaAudience = audienceText ? `, chuyên viết cho đối tượng độc giả: ${audienceText}` : '';
                const personaTopic = promptTopic ? ` về chủ đề "${promptTopic}"` : '';

                const top = [];
                top.push(`Bạn là biên tập viên giàu kinh nghiệm${category ? ` của chuyên mục "${category.name}"` : ''}${personaAudience}, đang nghiên cứu ý tưởng bài viết mới${personaTopic}.`);
                top.push(`Ngày hôm nay: ${new Date().toISOString().slice(0, 10)}.`);
                if (foundation?.core_focus) top.push(`Trọng tâm nội dung chuyên mục: ${foundation.core_focus}`);
                if (foundation?.unique_angle) top.push(`Góc nhìn khác biệt của chuyên mục: ${foundation.unique_angle}`);
                if (foundation?.content_goals) top.push(`Mục tiêu nội dung: ${foundation.content_goals}`);
                if (foundation?.pain_points) top.push(`Pain points / câu hỏi thường gặp của độc giả (từ nghiên cứu thực tế): ${foundation.pain_points}`);
                if (foundation?.rejected_ideas) top.push(`Ý tưởng đã cân nhắc và quyết định KHÔNG viết (Decision Log — không đề xuất lại): ${foundation.rejected_ideas}`);
                if (this.existingArticleTitles.length) {
                    top.push(`Bài đã publish trong chuyên mục này (${this.existingArticleTitles.length} bài, KHÔNG đề xuất trùng):`);
                    this.existingArticleTitles.forEach(title => top.push(`- ${title}`));
                }
                if (brief.goal) top.push(`Mục tiêu bài viết: ${brief.goal}`);
                if (brief.constraints) top.push(`Ràng buộc / không muốn: ${brief.constraints}`);
                if (brief.style_sample) top.push(`Giọng văn mẫu:\n${brief.style_sample}`);

                // `brief`/`topic` đã đưa lên TOP ở trên nên loại khỏi JSON ở MIDDLE (tránh lặp lại
                // cùng 1 thông tin 2 lần, giảm "signal density" — xem getcollate.io/learning-center/
                // context-engineering); các field kỹ thuật thuần khác cũng loại bỏ ở đây — xem
                // buildAiPayload(). Nút "Copy JSON" riêng (prettyJson()) không bị ảnh hưởng, vẫn
                // xuất bản đầy đủ nguyên trạng cho debug/audit.
                const promptData = this.buildAiPayload();

                const middle = [
                    'Dữ liệu thô đã trích xuất (tham khảo để lấy ý — KHÔNG copy nguyên văn; vài field thuần kỹ thuật đã lược bớt so với JSON gốc để đỡ tốn token). '
                        + 'Chú giải nhanh vài trường dễ hiểu nhầm: `declared_content_type`/`publisher_name` là do CHÍNH trang web tự khai báo (không phải suy đoán); '
                        + '`extraction_confidence`/`notes` phản ánh chất lượng TRÍCH XUẤT KỸ THUẬT, không phải chất lượng nội dung bài viết; '
                        + '`content_type_signal` (listicle/how_to/review_comparison/faq/product/product_faq) là PHỎNG ĐOÁN bằng rule đơn giản trên pattern heading/tiêu đề/loại trang '
                        + '(KHÔNG phải AI phân tích nội dung) — có thể sai hoặc null nếu không rõ, tự đối chiếu lại với main_content trước khi dùng:',
                    JSON.stringify(promptData, null, 2),
                ];

                const bottom = [
                    'Nhiệm vụ: đề xuất ý tưởng bài viết mới từ dữ liệu trên, làm theo đúng 3 bước sau.',
                ];

                if (hasNonVietnameseSource) {
                    bottom.push(`Nguồn tham khảo có ngôn ngữ gốc khác tiếng Việt (${[...sourceLanguages].join(', ')}) — LUÔN viết TOÀN BỘ output (ý tưởng, lý do, tiêu đề đề xuất) bằng tiếng Việt tự nhiên cho độc giả Việt Nam, KHÔNG dịch nguyên văn/máy móc câu chữ hay tiêu đề gốc.`);
                }

                bottom.push(
                    '',
                    'BƯỚC 1 — Sinh ý tưởng: brainstorm RỘNG, liệt kê 20-25 ý tưởng ứng viên đa dạng góc nhìn (chưa lọc) — '
                        + 'không chỉ biến tấu lại vài ý giống nhau. Đa dạng hoá bằng nhiều dạng góc nhìn khác nhau từ dữ liệu nguồn: '
                        + 'theo giai đoạn/độ tuổi, theo vấn đề cụ thể, theo đối tượng đặc thù (VD mẹ đi làm, sinh non, sinh đôi), '
                        + 'dạng so sánh/đối chiếu, dạng checklist/hướng dẫn chọn, dạng sai lầm thường gặp, dạng FAQ.',
                    'Riêng ý tưởng liên quan sức khoẻ/dinh dưỡng/an toàn trẻ em: KHÔNG đề xuất theo hướng khẳng định chắc chắn '
                        + 'các mẹo dân gian hay claim y khoa chưa được kiểm chứng khoa học — ưu tiên góc nhìn cần tham vấn '
                        + 'chuyên gia/dựa trên nguồn uy tín, khách quan (sai sót ở nhóm chủ đề này ảnh hưởng trực tiếp tới '
                        + 'sức khoẻ độc giả, không đơn thuần là 1 ý tưởng bài viết dở).',
                );

                if (successfulSourceCount >= 2) {
                    bottom.push('Trong đó BẮT BUỘC có ít nhất 1 ý tưởng TỔNG HỢP CHÉO từ ≥2 nguồn khác nhau ở trên (kết hợp insight của nhiều nguồn thành 1 góc nhìn mà không nguồn đơn lẻ nào tự có) — đây là dạng ý tưởng khó bị sao chép nhất.');

                    const commonKeywords = this.result.common_keywords ?? [];
                    if (commonKeywords.length) {
                        bottom.push(`Điểm chung giữa các nguồn (common_keywords): ${commonKeywords.join(', ')} — có thể dùng làm điểm khởi đầu cho ý tưởng tổng hợp chéo ở trên.`);
                    }

                    const modifiedDates = new Set((this.result.sources ?? [])
                        .filter(s => s.status === 'success' && s.date_modified)
                        .map(s => s.date_modified));
                    if (modifiedDates.size >= 2) {
                        bottom.push('Các nguồn có thời điểm cập nhật (date_modified) khác nhau — khi thông tin giữa các nguồn mâu thuẫn, ưu tiên nguồn có date_modified gần đây hơn.');
                    }

                    const contentTypeSignals = (this.result.sources ?? [])
                        .filter(s => s.status === 'success' && s.content_type_signal)
                        .map(s => s.content_type_signal);
                    const dominantSignal = contentTypeSignals.length >= 2 && new Set(contentTypeSignals).size === 1 ? contentTypeSignals[0] : null;
                    if (dominantSignal) {
                        bottom.push(`Đa số nguồn đều có content_type_signal = "${dominantSignal}" — cân nhắc chọn định dạng bài KHÁC (VD nếu nguồn toàn dạng liệt kê/listicle, thử viết dạng phân tích chuyên sâu hoặc so sánh) để tránh trùng hình thức với các nguồn tham khảo.`);
                    }
                }

                if (foundation?.rejected_ideas || this.existingArticleTitles.length) {
                    bottom.push('KHÔNG đề xuất ý tưởng trùng/gần giống bài đã publish hoặc ý tưởng đã bị từ chối liệt kê ở phần bối cảnh trên.');
                }

                bottom.push(
                    'Mục tiêu số lượng: Bảng 1 cần có ÍT NHẤT 10 ý tưởng đạt cả 4 tiêu chí. Nếu ở Bước 2 chưa đủ 10 ý đạt, quay lại '
                        + 'Bước 1 sinh thêm ý tưởng MỚI ở góc nhìn khác (không lặp ý đã liệt kê) cho đến khi đủ 10 — chỉ dừng dưới 10 '
                        + 'nếu đã thực sự khai thác hết góc nhìn hợp lý từ dữ liệu nguồn, và khi đó ghi rõ lý do ở cuối Bảng 2 '
                        + '(VD: dữ liệu nguồn không đủ sâu để tạo thêm ý tưởng chất lượng — KHÔNG được bịa ý tưởng yếu/generic chỉ để đủ số lượng).',
                );

                bottom.push(
                    '',
                    'BƯỚC 2 — Đánh giá TỪNG ý tưởng qua cả 4 tiêu chí (không bỏ qua tiêu chí nào, kể cả khi câu trả lời là "Không"):',
                    coreFocusText
                        ? `1. Khớp trọng tâm ("${coreFocusText}"): ý tưởng có thực sự gắn với trọng tâm này không?`
                        : '1. Khớp trọng tâm: có gắn với trọng tâm nội dung của chuyên mục này không?',
                    uniqueAngleText
                        ? `2. Góc nhìn độc quyền ("${uniqueAngleText}"): ý tưởng có thực sự thể hiện góc nhìn này không, hay điều nguồn nào cũng viết được?`
                        : '2. Góc nhìn độc quyền: đây có phải insight mà chỉ chuyên mục này viết được, không phải điều nguồn nào cũng viết được?',
                    goalText
                        ? `3. Phục vụ mục tiêu ("${goalText}"): ý tưởng có thực sự phục vụ mục tiêu này không?`
                        : '3. Phục vụ mục tiêu: có phục vụ mục tiêu nội dung đã nêu ở trên không?',
                    audienceText
                        ? `4. Phù hợp đối tượng độc giả ("${audienceText}"): góc độ/độ phức tạp/giọng văn của ý tưởng có thực sự phù hợp với đối tượng này không?`
                        : '4. Phù hợp đối tượng độc giả: ý tưởng có phù hợp với đối tượng độc giả đã nêu ở trên không?',
                    'Lưu ý khi đánh giá: nếu nguồn có extraction_confidence thấp hoặc notes cảnh báo nghi vấn paywall, hạ độ tin cậy khi dùng nguồn đó làm căn cứ cho ý tưởng.',
                    '',
                    'BƯỚC 3 — Trả lời bằng ĐÚNG 2 bảng Markdown dưới đây. Không viết giải thích, không mở đầu, không kết luận, không viết gì khác ngoài 2 bảng:',
                    '',
                    'Bảng 1 — Ý tưởng ĐẠT cả 4 tiêu chí, cột: '
                        + '| Ý tưởng | Khớp trọng tâm? | Góc nhìn độc quyền? | Phục vụ mục tiêu? | Phù hợp đối tượng? | Lý do (1 câu, vì sao đạt cả 4) | Đề xuất tiêu đề bài viết |',
                    'Bảng 2 — Ý tưởng BỊ LOẠI (không đạt ít nhất 1 tiêu chí) — LUÔN liệt kê nếu có ý tưởng bị loại ở Bước 1, không được bỏ qua bảng này, cột: '
                        + '| Ý tưởng bị loại | Tiêu chí không đạt | Lý do loại |',
                );

                const prompt = [...top, '', ...middle, '', ...bottom].join('\n');

                await navigator.clipboard.writeText(prompt);
                this.copiedPrompt = true;
                setTimeout(() => { this.copiedPrompt = false; }, 2000);
            },
        };
    });
});
</script>
@endpush
