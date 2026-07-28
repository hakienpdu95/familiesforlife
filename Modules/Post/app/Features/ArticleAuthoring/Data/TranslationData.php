<?php

namespace Modules\Post\Features\ArticleAuthoring\Data;

use Spatie\LaravelData\Attributes\Validation\Max;
use Spatie\LaravelData\Attributes\Validation\Required;
use Spatie\LaravelData\Data;

class TranslationData extends Data
{
    public function __construct(
        #[Required, Max(300)]
        public readonly string $title,

        public readonly ?string $slug = null, // null → auto-generate từ title (CreateTranslationAction/UpdateTranslationAction)
        public readonly ?string $excerpt = null,
        public readonly ?string $seo_title = null,
        public readonly ?string $seo_description = null,
        // AEO — câu trả lời trực tiếp hiển thị nổi bật đầu bài (khuyến nghị ~60 từ), khác
        // excerpt (mô tả/preview chung chung) — xem ArticleStructuredDataBuilder.
        public readonly ?string $direct_answer = null,

        /** @var array<int, array> Dãy block-composer — cùng shape với ArticleData::$blocks. */
        public readonly array $blocks = [],

        // required-if khi article.is_sponsored — không đặt attribute ở đây vì điều kiện tham
        // chiếu ArticleData, 1 DTO khác (spec/dac-ta-ky-thuat-bai-viet-tai-tro.md §6.2); validate
        // thật nằm ở TranslationController::validated() bằng Rule::requiredIf().
        public readonly ?string $disclosure_text = null,
        public readonly ?string $cta_text = null,
        public readonly ?string $cta_url = null,
    ) {}
}
