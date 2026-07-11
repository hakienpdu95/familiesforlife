<?php

namespace Modules\Post\Features\ArticleAuthoring\Data;

use Modules\Post\Enums\ArticleFormat;
use Spatie\LaravelData\Data;

/**
 * Dữ liệu cấp PostArticle (dùng chung mọi ngôn ngữ) — title/excerpt/seo_title/seo_description/blocks
 * đã chuyển sang TranslationData (per-locale), xem spec/PublishingEngine_Technical_Specification.md §2.
 */
class ArticleData extends Data
{
    public function __construct(
        public readonly ArticleFormat $format = ArticleFormat::Article,
        public readonly ?string $cover_image_url = null,
        public readonly bool $is_featured = false,
        public readonly ?string $main_locale = null,

        /** @var int[] */
        public readonly array $category_ids = [],
        public readonly ?int $is_primary_category_id = null,

        /** Tên tag, phân tách bởi dấu phẩy — tự tạo tag chưa tồn tại (cross-cutting, không cần UI quản lý riêng). */
        public readonly ?string $tags = null,
    ) {}
}
