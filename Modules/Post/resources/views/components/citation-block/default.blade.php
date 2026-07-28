{{--
    GEO đợt 4 (2026-07-28) — "citation engineering": trích dẫn/thống kê có nguồn ĐẶT TÊN rõ ràng
    (nghiên cứu Princeton/KDD 2024: thêm nguồn tăng tới +115% khả năng được AI trích dẫn). Nguồn
    này cũng được đưa vào Article.citation trong JSON-LD (xem ArticleStructuredDataBuilder).
--}}
<blockquote class="not-prose border-l-4 border-primary bg-base-100 pl-4 py-3 my-4">
    <p class="text-base-content/90 italic">{{ $block->citation_text }}</p>
    <footer class="text-xs text-base-content/50 mt-1.5">
        — @if($block->citation_source_url)<a href="{{ $block->citation_source_url }}" target="_blank" rel="nofollow noopener" class="hover:underline">{{ $block->citation_source_name }}</a>@else{{ $block->citation_source_name }}@endif
    </footer>
</blockquote>
