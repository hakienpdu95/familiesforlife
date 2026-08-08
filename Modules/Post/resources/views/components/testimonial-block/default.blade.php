{{--
    GEO đợt 7 (2026-08-08, đối chiếu spec/giadinh.md — "Product-Led Content Strategy: Turning
    Content Into Pipeline") — lời chứng thực khách hàng thật (social proof/conversion), KHÁC
    citation-block (trích dẫn số liệu/nguồn nghiên cứu bên thứ 3, phục vụ "citation engineering").

    CỐ Ý KHÔNG có JSON-LD Review đi kèm (khác Faq/Howto/Product) — xem quyết định tương ứng ở
    ArticleStructuredDataBuilder: schema.org Review yêu cầu `itemReviewed` trỏ tới 1 entity cụ thể
    (Product/LocalBusiness...) kèm ngữ cảnh đánh giá rõ ràng — 1 lời chứng thực nhúng trong bài
    viết không có entity đó, ép vào Review có thể bị Google coi là dùng sai structured data
    (Review/Rating markup abuse là 1 loại vi phạm guideline đã biết) — an toàn hơn khi chỉ render
    semantic HTML sạch (figure/figcaption) mà không gắn structured data không đúng ngữ cảnh.

    avatar_url dán TRỰC TIẾP (không qua Media Library) — cùng nguyên tắc Video §0: nếu khách hàng
    đã có ảnh công khai sẵn (VD từ mạng xã hội họ đồng ý dùng), tự tải/lưu lại là dư thừa.
--}}
<figure class="not-prose border border-base-300 rounded-lg p-4 my-4 bg-base-100">
    <blockquote class="text-base-content/90 italic">
        “{{ $block->testimonial_quote }}”
    </blockquote>
    <figcaption class="flex items-center gap-3 mt-3">
        @if($block->testimonial_avatar_url)
        <img src="{{ $block->testimonial_avatar_url }}" alt="{{ $block->testimonial_person_name }}"
             loading="lazy" class="w-10 h-10 rounded-full object-cover shrink-0">
        @endif
        <div class="text-sm">
            <span class="font-semibold text-base-content">{{ $block->testimonial_person_name }}</span>
            @if($block->testimonial_person_title || $block->testimonial_company_name)
            <span class="text-base-content/60">
                — {{ $block->testimonial_person_title }}{{ $block->testimonial_person_title && $block->testimonial_company_name ? ', ' : '' }}{{ $block->testimonial_company_name }}
            </span>
            @endif
        </div>
        @if($block->testimonial_result_metric)
        <span class="badge badge-success badge-sm ml-auto shrink-0">{{ $block->testimonial_result_metric }}</span>
        @endif
    </figcaption>
</figure>
