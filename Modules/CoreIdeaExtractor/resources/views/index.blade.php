@extends('layouts.backend')
@section('title', 'Trích xuất nội dung bài viết')

@section('content')
<div x-data="coreIdeaExtractorPage({{ Js::from([
    'apiUrl' => route('backend.api.coreideaextractor.extract'),
    'apiBatchUrl' => route('backend.api.coreideaextractor.extract-batch'),
    'maxUrls' => config('core_idea_extractor.batch.max_urls', 7),
    'categoryFoundationsUrl' => route('backend.coreideaextractor.category-foundations.index'),
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

            <pre class="bg-base-200 rounded-lg p-4 text-xs overflow-x-auto max-h-[70vh]" x-text="prettyJson()"></pre>
        </div>
    </div>

</div>
@endsection

@push('scripts')
<script>
document.addEventListener('alpine:init', () => {
    Alpine.data('coreIdeaExtractorPage', (serverData = {}) => {
        const { apiUrl = '', apiBatchUrl = '', maxUrls = 7, categoryFoundationsUrl = '', categories = [] } = serverData;

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
            forceRefresh: false,
            loading: false,
            result: null,
            errorMessage: '',
            copied: false,
            copiedPrompt: false,
            maxUrls,
            categoryFoundationsUrl,
            categories,
            selectedCategoryUuid: '',

            parsedUrls() {
                return [...new Set(
                    this.urlsText.split('\n').map(u => u.trim()).filter(Boolean)
                )];
            },

            parsedUrlCount() {
                return this.parsedUrls().length;
            },

            selectedCategory() {
                return this.categories.find(cat => cat.uuid === this.selectedCategoryUuid) ?? null;
            },

            /**
             * Prefill CÁC field ad-hoc hiện có (audience/goal/constraints/styleSample) từ
             * Category Content Foundation đã lưu — vẫn để người dùng tự sửa tiếp cho lần chạy
             * này, không khoá field (spec/CoreIdeaExtractor.md §12, v1.4).
             */
            applyCategoryFoundation() {
                const foundation = this.selectedCategory()?.foundation;
                if (!foundation) return;

                this.audience = foundation.audience || this.audience;
                this.goal = foundation.content_goals || this.goal;
                this.constraints = foundation.constraints || this.constraints;
                this.styleSample = foundation.style_sample || this.styleSample;
            },

            selectedFoundationSummary() {
                const foundation = this.selectedCategory()?.foundation;
                if (!foundation) return '';

                const parts = [];
                if (foundation.core_focus) parts.push(`Trọng tâm: ${foundation.core_focus}`);
                if (foundation.unique_angle) parts.push(`Góc nhìn khác biệt: ${foundation.unique_angle}`);

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
                const body = isBatch
                    ? JSON.stringify({
                        urls: this.parsedUrls().slice(0, this.maxUrls),
                        topic: this.topic || null,
                        audience: this.audience || null,
                        goal: this.goal || null,
                        constraints: this.constraints || null,
                        style_sample: this.styleSample || null,
                        main_content_selector: this.contentSelector || null,
                        force_refresh: this.forceRefresh,
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

            /**
             * "Context sandwich" (https://www.mindstudio.ai/blog/context-sandwich-prompting-method-ai-results)
             * + context engineering (https://www.promptingguide.ai/guides/context-engineering-guide):
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
             * Không gọi AI Provider nào ở backend — giữ triết lý "công cụ nghiên cứu, copy tay"
             * hiện có.
             */
            async copyPromptForAi() {
                if (!this.result) return;

                const category = this.selectedCategory();
                const foundation = category?.foundation;
                const successfulSourceCount = this.isBatchResult()
                    ? (this.result.sources ?? []).filter(s => s.status === 'success').length
                    : 1;

                const top = [];
                top.push(`Bạn là biên tập viên giàu kinh nghiệm${category ? ` của chuyên mục "${category.name}"` : ''}, đang nghiên cứu ý tưởng bài viết mới.`);
                top.push(`Ngày hôm nay: ${new Date().toISOString().slice(0, 10)}.`);
                if (foundation?.core_focus) top.push(`Trọng tâm nội dung chuyên mục: ${foundation.core_focus}`);
                if (foundation?.unique_angle) top.push(`Góc nhìn khác biệt của chuyên mục: ${foundation.unique_angle}`);
                if (foundation?.content_goals) top.push(`Mục tiêu nội dung: ${foundation.content_goals}`);
                if (this.audience) top.push(`Đối tượng độc giả: ${this.audience}`);
                if (this.goal) top.push(`Mục tiêu bài viết: ${this.goal}`);
                if (this.constraints) top.push(`Ràng buộc / không muốn: ${this.constraints}`);
                if (this.styleSample) top.push(`Giọng văn mẫu:\n${this.styleSample}`);

                const middle = [
                    'Dữ liệu thô đã trích xuất (tham khảo để lấy ý — KHÔNG copy nguyên văn):',
                    this.prettyJson(),
                ];

                const bottom = [
                    'Nhiệm vụ: đề xuất ý tưởng bài viết mới từ dữ liệu trên, làm theo đúng 3 bước sau.',
                    '',
                    'BƯỚC 1 — Sinh ý tưởng: liệt kê tối đa 8-10 ý tưởng ứng viên (chưa lọc).',
                ];

                if (successfulSourceCount >= 2) {
                    bottom.push('Trong đó BẮT BUỘC có ít nhất 1 ý tưởng TỔNG HỢP CHÉO từ ≥2 nguồn khác nhau ở trên (kết hợp insight của nhiều nguồn thành 1 góc nhìn mà không nguồn đơn lẻ nào tự có) — đây là dạng ý tưởng khó bị sao chép nhất.');
                }

                bottom.push(
                    '',
                    'BƯỚC 2 — Đánh giá TỪNG ý tưởng qua cả 3 tiêu chí (không bỏ qua tiêu chí nào, kể cả khi câu trả lời là "Không"):',
                    '1. Khớp trọng tâm: có gắn với trọng tâm nội dung của chuyên mục này không?',
                    '2. Góc nhìn độc quyền: đây có phải insight mà chỉ chuyên mục này viết được, không phải điều nguồn nào cũng viết được?',
                    '3. Phục vụ mục tiêu: có phục vụ mục tiêu nội dung đã nêu ở trên không?',
                    '',
                    'BƯỚC 3 — Trả lời bằng ĐÚNG 2 bảng Markdown dưới đây. Không viết giải thích, không mở đầu, không kết luận, không viết gì khác ngoài 2 bảng:',
                    '',
                    'Bảng 1 — Ý tưởng ĐẠT cả 3 tiêu chí, cột: '
                        + '| Ý tưởng | Khớp trọng tâm? | Góc nhìn độc quyền? | Phục vụ mục tiêu? | Lý do (1 câu, vì sao đạt cả 3) | Đề xuất tiêu đề bài viết |',
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
