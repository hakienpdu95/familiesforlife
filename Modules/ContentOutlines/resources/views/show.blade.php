@extends('layouts.backend')
@section('title', $outline->label)

@php
    // §4.24 (v1.20) — dùng để cảnh báo cascade khi "Sửa & Sinh lại" (xem edit.blade.php) và tô màu
    // stepper Bước 1→2→3 bên dưới.
    $hasDownstreamSteps = filled($outline->article_draft_prompt) || filled($outline->review_prompt);
@endphp

@section('content')

@foreach(['success','error'] as $type)
    @if(session($type))
    <div class="alert alert-{{ $type }} mb-4 text-sm">
        <span>{{ session($type) }}</span>
    </div>
    @endif
@endforeach

<div class="flex items-center justify-between mb-6">
    <div>
        <h1 class="text-2xl font-bold text-base-content">{{ $outline->label }}</h1>
        <p class="text-sm text-base-content/50 mt-0.5">
            {{ $outline->topic }} — từ khoá: <code>{{ $outline->target_keyword }}</code>
            @if($outline->category)
                — chuyên mục: {{ $outline->category->name }}
            @endif
        </p>
    </div>
    <div class="flex items-center gap-2">
        <a href="{{ route('backend.contentoutlines.edit', $outline) }}" class="btn btn-ghost btn-sm gap-1.5"
           @if($hasDownstreamSteps) title="Đã có Bước 2/3 — sinh lại outline có thể làm nội dung đó không còn khớp." @endif>
            <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"/></svg>
            Sửa &amp; Sinh lại
            @if($hasDownstreamSteps)<span class="text-warning">⚠</span>@endif
        </a>
        <a href="{{ route('backend.contentoutlines.index') }}" class="btn btn-ghost btn-sm">Danh sách</a>
    </div>
</div>

{{-- §4.24 (v1.20) — stepper điều hướng + trạng thái hoàn thành, cải thiện "visual hierarchy"
     Bước 1→2→3 giữa 3 khối prompt lớn trên cùng 1 trang (rủi ro UX đã ghi nhận, xem spec §4.24). --}}
<div class="flex items-center gap-2 mb-4 text-xs">
    <a href="#buoc-1" class="badge badge-sm badge-success gap-1">Bước 1 ✓</a>
    <span class="text-base-content/30">→</span>
    <a href="#buoc-2" class="badge badge-sm {{ $outline->article_draft_prompt ? 'badge-success' : 'badge-ghost' }} gap-1">Bước 2{{ $outline->article_draft_prompt ? ' ✓' : '' }}</a>
    <span class="text-base-content/30">→</span>
    <a href="#buoc-3" class="badge badge-sm {{ $outline->review_prompt ? 'badge-success' : 'badge-ghost' }} gap-1">Bước 3{{ $outline->review_prompt ? ' ✓' : '' }}</a>
</div>

{{-- §4.1 (v1.1) — soft warning, KHÔNG chặn gì — chỉ cảnh báo prompt có thể bị cắt/giảm chất
     lượng ở 1 số AI ngoài khi vượt ngưỡng BuildContentOutlinePromptAction::WORD_COUNT_WARNING_THRESHOLD. --}}
@if($promptIsLong)
<div class="alert py-3 px-4 mb-4 bg-warning/10 border border-warning/40 text-sm">
    <svg class="w-5 h-5 shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"/></svg>
    <span>Prompt dài <b>~{{ number_format($promptWordCount, 0, ',', '.') }} từ</b> — 1 số AI ngoài có thể cắt hoặc giảm chất lượng phản hồi với prompt quá dài. Cân nhắc sửa &amp; chọn "Rút gọn (brief)" ở độ chi tiết, hoặc bớt nguồn tham khảo/ghi chú.</span>
</div>
@endif

<div id="buoc-1" class="grid grid-cols-1 xl:grid-cols-[1fr_320px] gap-6 items-start">

    <div class="card bg-base-100 shadow-sm border border-base-200" x-data="{ view: 'raw' }">
        <div class="card-body">
            <div class="flex items-center justify-between mb-3 flex-wrap gap-2">
                <h2 class="card-title text-base">Prompt đã sinh</h2>
                <div class="flex items-center gap-2">
                    <div role="tablist" class="tabs tabs-boxed tabs-sm">
                        <a role="tab" class="tab" :class="view === 'raw' ? 'tab-active' : ''" @click="view = 'raw'">Prompt thô</a>
                        <a role="tab" class="tab" :class="view === 'preview' ? 'tab-active' : ''" @click="view = 'preview'; $nextTick(() => window.contentOutlineMakeCollapsible?.('content-outline-preview'))">Xem trước Markdown</a>
                    </div>
                    <button type="button" class="btn btn-ghost btn-sm gap-1.5" onclick="window.contentOutlineDownloadPrompt('content-outline-prompt', '{{ \Illuminate\Support\Str::slug($outline->label) }}.md')">
                        <svg class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M4 16v2a2 2 0 002 2h12a2 2 0 002-2v-2M7 10l5 5 5-5M12 15V3"/></svg>
                        Download .md
                    </button>
                    <button type="button" id="content-outline-copy-btn"
                            class="btn btn-primary btn-sm gap-1.5 transition-all duration-200 ease-out"
                            onclick="window.contentOutlineCopyPrompt('content-outline-prompt', this)">
                        <svg class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M8 16H6a2 2 0 01-2-2V6a2 2 0 012-2h8a2 2 0 012 2v2m-6 12h8a2 2 0 002-2v-8a2 2 0 00-2-2h-8a2 2 0 00-2 2v8a2 2 0 002 2z"/></svg>
                        <span>Copy prompt</span>
                    </button>
                </div>
            </div>

            <textarea id="content-outline-prompt" x-show="view === 'raw'" readonly rows="24"
                      class="textarea textarea-bordered w-full font-mono text-xs leading-relaxed">{{ $outline->generated_prompt }}</textarea>

            {{-- §4.5 (v1.1) — Markdown preview qua Str::markdown() (server, league/commonmark có
                 sẵn trong vendor) — nhẹ hơn thêm 1 thư viện JS mới. Collapsible theo từng "## "
                 thực hiện ở client (content-outlines.js), gom node sau mỗi <h2> vào <details>. --}}
            <div id="content-outline-preview" x-show="view === 'preview'" x-cloak
                 class="prose prose-sm max-w-none border border-base-200 rounded-lg p-4 overflow-x-auto">
                {!! $promptHtml !!}
            </div>

            <p class="text-xs text-base-content/40 mt-2">Dán đoạn prompt trên vào ChatGPT/Claude/Gemini (bản có web search/browsing cho kết quả research tốt nhất) để nhận outline hoàn chỉnh.</p>
        </div>
    </div>

    <div class="space-y-4">
        <div class="card bg-base-100 shadow-sm border border-base-200">
            <div class="card-body p-4">
                {{-- §4.4 (v1.1) — liên kết 1-1: chỉ gắn được TỐI ĐA 1 PostArticle tại 1 thời điểm,
                     xem spec §8 "Ngoài phạm vi". --}}
                <p class="text-xs font-semibold text-base-content/40 uppercase tracking-wide mb-3">Gắn vào bài viết (tối đa 1 bài)</p>
                @if($outline->linkedArticle)
                    <p class="text-sm mb-2">Đã gắn: <b>{{ $outline->linkedArticle->mainTranslation()?->title ?? '(chưa có tiêu đề)' }}</b></p>
                    <form method="POST" action="{{ route('backend.contentoutlines.link-article', $outline) }}">
                        @csrf
                        <input type="hidden" name="post_article_uuid" value="">
                        <button type="submit" class="btn btn-ghost btn-xs text-error">Gỡ liên kết</button>
                    </form>
                @else
                    <form method="POST" action="{{ route('backend.contentoutlines.link-article', $outline) }}" class="space-y-2">
                        @csrf
                        <input type="text" name="post_article_uuid" class="input input-bordered input-sm w-full font-mono text-xs"
                               placeholder="Dán UUID bài viết (từ URL sửa bài viết)">
                        <button type="submit" class="btn btn-primary btn-xs w-full">Gắn</button>
                    </form>
                @endif
            </div>
        </div>

        <div class="card bg-base-100 shadow-sm border border-base-200">
            <div class="card-body p-4 text-xs text-base-content/60 space-y-1.5">
                <p><b>Tạo bởi:</b> {{ $outline->createdBy?->name ?? '—' }}</p>
                <p><b>Ngày tạo:</b> {{ $outline->created_at?->format('d/m/Y H:i') }}</p>
                <p><b>Cập nhật gần nhất:</b> {{ $outline->updated_at?->format('d/m/Y H:i') }}</p>
                <p><b>Ngôn ngữ đầu ra:</b> {{ $outline->language === 'en' ? 'English' : 'Tiếng Việt' }}</p>
                <p><b>Độ chi tiết:</b> {{ ['brief' => 'Rút gọn', 'standard' => 'Chuẩn', 'detailed' => 'Chi tiết'][$outline->outline_depth] ?? $outline->outline_depth }}</p>
                @if($outline->content_role)
                <p><b>Vai trò nội dung:</b> {{ $outline->content_role === 'pillar' ? 'Trụ cột (pillar)' : 'Cụm (cluster)' }}</p>
                @endif
                @if($outline->cta_url)
                <p><b>CTA URL:</b> <a href="{{ $outline->cta_url }}" target="_blank" rel="noopener" class="link link-hover break-all">{{ $outline->cta_url }}</a></p>
                @endif
                <p><b>Độ dài prompt:</b> ~{{ number_format($promptWordCount, 0, ',', '.') }} từ</p>
            </div>
        </div>
    </div>

</div>

{{-- §4.17 (v1.14) — "Bước 2": dán lại outline đã duyệt (AI ngoài trả về sau khi chạy prompt ở
     trên) để sinh 1 prompt ĐỘC LẬP, đã nhúng SẴN outline đó, dùng ở 1 lượt chat AI MỚI để viết bài
     hoàn chỉnh. KHÔNG gọi AI Provider nào trong app (§0 mục 1) — chỉ sinh thêm 1 đoạn text, cùng
     cách module hoạt động từ đầu. --}}
<div id="buoc-2" class="card bg-base-100 shadow-sm border border-base-200 mt-6">
    <div class="card-body">
    {{-- §4.24 (v1.20/v1.21) — <details> mặc định ĐÓNG khi bước CHƯA DÙNG (chưa có article_draft_prompt)
         để giảm chiều cao trang khi mới vào (chỉ 1 click để mở khi cần) — mặc định MỞ khi đã có
         kết quả (biên tập viên quay lại xem/copy). Biên tập viên tự mở/đóng bất kỳ lúc nào, nội
         dung KHÔNG bị mất khi đóng. --}}
    <details @if($outline->article_draft_prompt) open @endif>
        <summary class="card-title text-base cursor-pointer">
            Bước 2 — Viết bài từ outline
            @if($outline->article_draft_prompt)<span class="badge badge-success badge-xs align-middle">Đã sinh</span>@endif
        </summary>
        <p class="text-xs text-base-content/40 mb-3 mt-1">Sau khi dán prompt ở trên vào AI ngoài và nhận outline hoàn chỉnh (đã đọc/sửa lại nếu cần), dán outline đó vào đây để sinh 1 prompt viết bài — dùng ở 1 lượt chat AI MỚI.</p>

        <form method="POST" action="{{ route('backend.contentoutlines.generate-article-prompt', $outline) }}"
              @if($outline->article_draft_prompt) data-confirm-regenerate="Sinh prompt viết bài mới sẽ GHI ĐÈ prompt hiện tại — KHÔNG thể khôi phục lại prompt cũ (không versioning). Tiếp tục?" @endif>
            @csrf
            <textarea name="approved_outline" rows="8" required maxlength="50000"
                      placeholder="Dán outline (Markdown) mà AI ngoài đã trả về ở đây..."
                      class="textarea textarea-bordered w-full font-mono text-xs leading-relaxed mb-3">{{ old('approved_outline', $outline->approved_outline) }}</textarea>
            <button type="submit" class="btn btn-primary btn-sm">{{ $outline->article_draft_prompt ? 'Lưu & Sinh lại prompt viết bài' : 'Lưu & Sinh prompt viết bài' }}</button>
        </form>

        @if($outline->article_draft_prompt)
        <div class="mt-5" x-data="{ view: 'raw' }">
            @if($articlePromptIsLong)
            <div class="alert py-3 px-4 mb-3 bg-warning/10 border border-warning/40 text-sm">
                <svg class="w-5 h-5 shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"/></svg>
                <span>Prompt dài <b>~{{ number_format($articlePromptWordCount, 0, ',', '.') }} từ</b> — phần lớn do outline đã dán dài, đây là BÌNH THƯỜNG (không phải dấu hiệu lỗi). Vẫn cân nhắc rút gọn outline nếu 1 số AI ngoài cắt/giảm chất lượng phản hồi.</span>
            </div>
            @endif

            <div class="flex items-center justify-between mb-3 flex-wrap gap-2">
                <div role="tablist" class="tabs tabs-boxed tabs-sm">
                    <a role="tab" class="tab" :class="view === 'raw' ? 'tab-active' : ''" @click="view = 'raw'">Prompt thô</a>
                    <a role="tab" class="tab" :class="view === 'preview' ? 'tab-active' : ''" @click="view = 'preview'; $nextTick(() => window.contentOutlineMakeCollapsible?.('content-outline-article-preview'))">Xem trước Markdown</a>
                </div>
                <div class="flex items-center gap-2">
                    <button type="button" class="btn btn-ghost btn-sm gap-1.5" onclick="window.contentOutlineDownloadPrompt('content-outline-article-prompt', '{{ \Illuminate\Support\Str::slug($outline->label) }}-bai-viet.md')">
                        <svg class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M4 16v2a2 2 0 002 2h12a2 2 0 002-2v-2M7 10l5 5 5-5M12 15V3"/></svg>
                        Download .md
                    </button>
                    <button type="button" id="content-outline-article-copy-btn"
                            class="btn btn-primary btn-sm gap-1.5 transition-all duration-200 ease-out"
                            onclick="window.contentOutlineCopyPrompt('content-outline-article-prompt', this)">
                        <svg class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M8 16H6a2 2 0 01-2-2V6a2 2 0 012-2h8a2 2 0 012 2v2m-6 12h8a2 2 0 002-2v-8a2 2 0 00-2-2h-8a2 2 0 00-2 2v8a2 2 0 002 2z"/></svg>
                        <span>Copy prompt</span>
                    </button>
                </div>
            </div>

            <textarea id="content-outline-article-prompt" x-show="view === 'raw'" readonly rows="16"
                      class="textarea textarea-bordered w-full font-mono text-xs leading-relaxed">{{ $outline->article_draft_prompt }}</textarea>

            <div id="content-outline-article-preview" x-show="view === 'preview'" x-cloak
                 class="prose prose-sm max-w-none border border-base-200 rounded-lg p-4 overflow-x-auto">
                {!! $articlePromptHtml !!}
            </div>

            <p class="text-xs text-base-content/40 mt-2">Dán đoạn prompt trên vào 1 lượt chat AI MỚI để nhận bài viết hoàn chỉnh — biên tập viên vẫn rà lại nội dung trước khi publish (§ "Lưu ý EEAT" trong prompt).</p>
        </div>
        @endif
    </details>
    </div>
</div>

{{-- §4.20 (v1.16) — "Bước 3": dán bài viết ĐÃ VIẾT XONG (từ AI ngoài chạy prompt Bước 2, hoặc viết
     tay) để sinh 1 prompt SOÁT LỖI/SỬA (SEO + độ dễ đọc + rà soát cuối, yêu cầu đề xuất sửa chính
     xác — KHÔNG viết lại toàn bài). KHÔNG gọi AI Provider nào trong app (§0 mục 1). --}}
<div id="buoc-3" class="card bg-base-100 shadow-sm border border-base-200 mt-6">
    <div class="card-body">
    {{-- §4.24 (v1.20/v1.21) — CÙNG quy tắc Bước 2: mặc định ĐÓNG khi chưa dùng (chưa có
         review_prompt), mở khi đã có kết quả. --}}
    <details @if($outline->review_prompt) open @endif>
        <summary class="card-title text-base cursor-pointer">
            Bước 3 — Soát lỗi/Sửa bài
            @if($outline->review_prompt)<span class="badge badge-success badge-xs align-middle">Đã sinh</span>@endif
        </summary>
        <p class="text-xs text-base-content/40 mb-3 mt-1">Sau khi có bài viết hoàn chỉnh (từ AI ngoài ở Bước 2, hoặc viết tay), dán bài đó vào đây để sinh 1 prompt soát lỗi/sửa (SEO, độ dễ đọc, rà soát cuối) — dùng ở 1 lượt chat AI MỚI.</p>

        <form method="POST" action="{{ route('backend.contentoutlines.generate-review-prompt', $outline) }}"
              @if($outline->review_prompt) data-confirm-regenerate="Sinh prompt soát lỗi mới sẽ GHI ĐÈ prompt hiện tại — KHÔNG thể khôi phục lại prompt cũ (không versioning). Tiếp tục?" @endif>
            @csrf
            <textarea name="drafted_article" rows="10" required maxlength="100000"
                      placeholder="Dán bài viết hoàn chỉnh (Markdown) vào đây..."
                      class="textarea textarea-bordered w-full font-mono text-xs leading-relaxed mb-3">{{ old('drafted_article', $outline->drafted_article) }}</textarea>
            <button type="submit" class="btn btn-primary btn-sm">{{ $outline->review_prompt ? 'Lưu & Sinh lại prompt soát lỗi' : 'Lưu & Sinh prompt soát lỗi' }}</button>
        </form>

        @if($outline->review_prompt)
        <div class="mt-5" x-data="{ view: 'raw' }">
            @if($reviewPromptIsLong)
            <div class="alert py-3 px-4 mb-3 bg-warning/10 border border-warning/40 text-sm">
                <svg class="w-5 h-5 shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"/></svg>
                <span>Prompt dài <b>~{{ number_format($reviewPromptWordCount, 0, ',', '.') }} từ</b> — phần lớn do bài viết đã dán dài, đây là BÌNH THƯỜNG (không phải dấu hiệu lỗi).</span>
            </div>
            @endif

            <div class="flex items-center justify-between mb-3 flex-wrap gap-2">
                <div role="tablist" class="tabs tabs-boxed tabs-sm">
                    <a role="tab" class="tab" :class="view === 'raw' ? 'tab-active' : ''" @click="view = 'raw'">Prompt thô</a>
                    <a role="tab" class="tab" :class="view === 'preview' ? 'tab-active' : ''" @click="view = 'preview'; $nextTick(() => window.contentOutlineMakeCollapsible?.('content-outline-review-preview'))">Xem trước Markdown</a>
                </div>
                <div class="flex items-center gap-2">
                    <button type="button" class="btn btn-ghost btn-sm gap-1.5" onclick="window.contentOutlineDownloadPrompt('content-outline-review-prompt', '{{ \Illuminate\Support\Str::slug($outline->label) }}-soat-loi.md')">
                        <svg class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M4 16v2a2 2 0 002 2h12a2 2 0 002-2v-2M7 10l5 5 5-5M12 15V3"/></svg>
                        Download .md
                    </button>
                    <button type="button" id="content-outline-review-copy-btn"
                            class="btn btn-primary btn-sm gap-1.5 transition-all duration-200 ease-out"
                            onclick="window.contentOutlineCopyPrompt('content-outline-review-prompt', this)">
                        <svg class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M8 16H6a2 2 0 01-2-2V6a2 2 0 012-2h8a2 2 0 012 2v2m-6 12h8a2 2 0 002-2v-8a2 2 0 00-2-2h-8a2 2 0 00-2 2v8a2 2 0 002 2z"/></svg>
                        <span>Copy prompt</span>
                    </button>
                </div>
            </div>

            <textarea id="content-outline-review-prompt" x-show="view === 'raw'" readonly rows="16"
                      class="textarea textarea-bordered w-full font-mono text-xs leading-relaxed">{{ $outline->review_prompt }}</textarea>

            <div id="content-outline-review-preview" x-show="view === 'preview'" x-cloak
                 class="prose prose-sm max-w-none border border-base-200 rounded-lg p-4 overflow-x-auto">
                {!! $reviewPromptHtml !!}
            </div>

            <p class="text-xs text-base-content/40 mt-2">Dán đoạn prompt trên vào 1 lượt chat AI MỚI để nhận đánh giá SEO/độ dễ đọc/rà soát cuối + đề xuất sửa cụ thể — biên tập viên tự áp dụng các đề xuất phù hợp, không nhất thiết phải nghe theo tất cả.</p>
        </div>
        @endif
    </details>
    </div>
</div>

@endsection

@push('scripts')
    @vite(['Modules/ContentOutlines/resources/assets/js/content-outlines.js'], 'build/backend')
@endpush
