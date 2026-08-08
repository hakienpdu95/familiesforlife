{{--
    GEO đợt 6 (2026-08-08) — "Comparison fan-out" (spec/giadinh.md — Moz "A Guide to Web Guide").
    CỐ Ý dùng bảng HTML ngữ nghĩa thuần (<table>/<caption>/<th scope>) — KHÔNG phát sinh JSON-LD
    riêng cho khối này (khác Faq/Howto/Product): schema.org không có type nào được công cụ/AI
    answer engine hỗ trợ tốt cho "bảng so sánh tuỳ ý" (không phải danh sách sản phẩm thật để dùng
    Product/Offer) — 1 bảng <table> đúng chuẩn semantic HTML/accessibility (th scope rõ ràng) đã
    là tín hiệu GEO tốt nhất hiện có cho dạng nội dung này, tự thân dễ được AI trích nguyên cấu
    trúc hàng/cột mà không cần thêm structured data có thể sai ngữ nghĩa nếu ép vào 1 @type không
    khớp (xem quyết định tương ứng ở ArticleStructuredDataBuilder — không có buildComparisons()).
--}}
<div class="not-prose my-4 overflow-x-auto">
    @if($block->name)
    <h2 class="font-semibold text-lg mb-1">{{ $block->name }}</h2>
    @endif
    @if($block->description)
    <p class="text-sm text-base-content/60 mb-3">{{ $block->description }}</p>
    @endif

    <table class="table table-sm border border-base-300">
        @if($block->name)
        <caption class="sr-only">{{ $block->name }}</caption>
        @endif
        <thead>
            <tr>
                <th scope="col"></th>
                @foreach($block->columns as $column)
                <th scope="col" class="font-semibold">{{ $column->label }}</th>
                @endforeach
            </tr>
        </thead>
        <tbody>
            @foreach($block->rows as $row)
            <tr>
                <th scope="row" class="font-medium text-left bg-base-200/50">{{ $row->label }}</th>
                @foreach($row->values as $value)
                <td>{{ $value }}</td>
                @endforeach
            </tr>
            @endforeach
        </tbody>
    </table>
</div>
