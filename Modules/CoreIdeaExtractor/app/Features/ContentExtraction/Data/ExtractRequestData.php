<?php

namespace Modules\CoreIdeaExtractor\Features\ContentExtraction\Data;

use Spatie\LaravelData\Attributes\Validation\In;
use Spatie\LaravelData\Attributes\Validation\Max;
use Spatie\LaravelData\Attributes\Validation\Nullable;
use Spatie\LaravelData\Attributes\Validation\Url;
use Spatie\LaravelData\Data;

class ExtractRequestData extends Data
{
    public function __construct(
        /** Bắt buộc trừ khi có $html — validate required_without ở controller (mảng rule thô, không phải attribute Spatie). */
        #[Nullable, Url, Max(2048)]
        public readonly ?string $url = null,
        /**
         * Mã HTML người dùng dán tay trực tiếp — lối thoát khi trang chặn crawl tự động (403/bot
         * protection, xem UrlFetchException). Có thể là CẢ trang (View Source) hoặc chỉ 1 đoạn
         * fragment (VD nội dung trong `<div class="post__content">`) — ExtractRawContentAction
         * parse được cả 2 dạng, chỉ khác là fragment sẽ không có title/meta/lang (nằm trong
         * `<head>`, không có trong đoạn dán).
         */
        #[Nullable]
        public readonly ?string $html = null,
        /** CSS selector đơn giản (id/class) do người dùng chỉ định để khoanh vùng main_content, VD ".detail-content", "#main-content". Null → dùng thuật toán tự động resolveContentRoot(). */
        #[Nullable, Max(255)]
        public readonly ?string $main_content_selector = null,
        /** true = bỏ qua cache HTML đã fetch trước đó (xem CachesFetchedHtml), luôn fetch mạng lại — dùng khi user nghi ngờ nội dung trang đã đổi hoặc site đã hết bị chặn. */
        public readonly bool $force_refresh = false,
        /**
         * Ngôn ngữ nguồn do người dùng tự chọn (vi/en/th/id) — GHI ĐÈ hoàn toàn kết quả tự
         * detect của ExtractRawContentAction (cả `<html lang>` khai báo lẫn đối chiếu ký tự script
         * ở resolveLanguage()), vì tự detect nhiều khi không chính xác (site khai lang sai, hoặc
         * không phải script không-Latin nên không đối chiếu được). Null → giữ nguyên hành vi tự
         * động cũ (dùng khi gọi API trực tiếp không qua form, VD test).
         */
        #[Nullable, In(['vi', 'en', 'th', 'id'])]
        public readonly ?string $source_language = null,
    ) {}
}
