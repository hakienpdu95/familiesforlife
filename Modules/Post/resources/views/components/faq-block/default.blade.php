{{--
    AEO (2026-07-28) — hiển thị khối "Câu hỏi thường gặp" dạng accordion (native <details>, không
    cần JS). Nội dung này CŨNG được sinh JSON-LD FAQPage riêng (xem ArticleStructuredDataBuilder) —
    giữ text ở đây và ở schema PHẢI khớp nhau (schema đọc thẳng từ cùng $block->items), không cần
    đồng bộ tay.
--}}
<div class="not-prose my-4">
    @if($block->heading)
    <h3 class="font-semibold text-lg mb-2">{{ $block->heading }}</h3>
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
