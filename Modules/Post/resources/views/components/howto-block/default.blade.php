{{--
    GEO đợt 4 (2026-07-28) — hiển thị "Hướng dẫn từng bước" dạng danh sách có thứ tự. Nội dung
    này CŨNG được sinh JSON-LD HowTo riêng (xem ArticleStructuredDataBuilder) — giữ text ở đây và
    ở schema PHẢI khớp nhau (schema đọc thẳng từ cùng $block->steps), không cần đồng bộ tay.
--}}
<div class="not-prose my-4">
    @if($block->name)
    <h3 class="font-semibold text-lg mb-1">{{ $block->name }}</h3>
    @endif
    @if($block->description)
    <p class="text-sm text-base-content/60 mb-3">{{ $block->description }}</p>
    @endif

    <ol class="flex flex-col gap-3">
        @foreach($block->steps as $step)
        <li class="flex gap-3 items-start">
            <span class="badge badge-primary badge-sm shrink-0 mt-0.5">{{ $loop->iteration }}</span>
            <div>
                <p class="font-medium text-sm">{{ $step->name }}</p>
                <p class="text-sm text-base-content/70">{{ $step->text }}</p>
            </div>
        </li>
        @endforeach
    </ol>
</div>
