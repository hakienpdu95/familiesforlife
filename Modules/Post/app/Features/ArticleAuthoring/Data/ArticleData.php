<?php

namespace Modules\Post\Features\ArticleAuthoring\Data;

use Modules\Post\Enums\ArticleFormat;
use Spatie\LaravelData\Attributes\Validation\Max;
use Spatie\LaravelData\Attributes\Validation\Required;
use Spatie\LaravelData\Data;

class ArticleData extends Data
{
    public function __construct(
        #[Required, Max(300)]
        public readonly string $title,

        public readonly ArticleFormat $format = ArticleFormat::Article,
        public readonly ?string $excerpt = null,

        /**
         * Dãy block-composer theo đúng thứ tự hiển thị — mỗi phần tử là
         * `['type' => 'text', 'html' => '...']` hoặc
         * `['type' => 'product', 'block_uuid' => ..., 'template' => ..., 'heading' => ...,
         *   'items' => [...], 'block_buttons' => [...]]`.
         * Được decode từ JSON (input ẩn `blocks_json`) trước khi tới đây — xem
         * ArticleAdminController::validated().
         *
         * @var array<int, array>
         */
        public readonly array $blocks = [],

        public readonly ?string $cover_image_url = null,
        public readonly ?string $seo_title = null,
        public readonly ?string $seo_description = null,
        public readonly bool $is_featured = false,

        /** @var int[] */
        public readonly array $category_ids = [],
        public readonly ?int $is_primary_category_id = null,

        /** Tên tag, phân tách bởi dấu phẩy — tự tạo tag chưa tồn tại (cross-cutting, không cần UI quản lý riêng). */
        public readonly ?string $tags = null,
    ) {}
}
