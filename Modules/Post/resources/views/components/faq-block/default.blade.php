{{--
    AEO (2026-07-28) — hiển thị khối "Câu hỏi thường gặp" dạng accordion (native <details>, không
    cần JS). Nội dung này CŨNG được sinh JSON-LD FAQPage riêng (xem ArticleStructuredDataBuilder) —
    giữ text ở đây và ở schema PHẢI khớp nhau (schema đọc thẳng từ cùng $block->items), không cần
    đồng bộ tay.

    Technical GEO (2026-07-28) — heading của khối dùng <h2>, KHÔNG phải <h3>: nội dung chính của
    bài (`text_html` từ Jodit) là HTML tự do do biên tập viên gõ, hệ thống không kiểm soát được họ
    có dùng H2 cho các mục lớn hay không — nếu khối này cứng <h3>, gặp trường hợp không có H2 nào
    trước đó sẽ nhảy cấp H1→H3. Dùng H2 (cùng cấp "mục lớn" hợp lý cho 1 khối FAQ/HowTo đứng độc
    lập trong bài) an toàn hơn dù editor có tự chèn H2 riêng hay không.
--}}
<div class="not-prose my-4">
    @if($block->heading)
    <h2 class="font-semibold text-lg mb-2">{{ $block->heading }}</h2>
    @endif

    <div class="flex flex-col gap-2">
        @foreach($block->items as $item)
        <details class="collapse collapse-plus bg-base-100 border border-base-200">
            <summary class="collapse-title text-sm font-medium min-h-0 py-3">{{ $item->question }}</summary>
            <div class="collapse-content text-sm text-base-content/70">
                <p>{{ $item->answer }}</p>
            </div>
        </details>
        @endforeach
    </div>
</div>
