<?php

namespace Modules\Post\Features\ArticleAuthoring\Data;

use Modules\Post\Enums\ArticleFormat;
use Modules\Post\Enums\SponsorLabel;
use Spatie\LaravelData\Data;

/**
 * Dữ liệu cấp PostArticle (dùng chung mọi ngôn ngữ) — title/excerpt/seo_title/seo_description/blocks
 * đã chuyển sang TranslationData (per-locale), xem spec/PublishingEngine_Technical_Specification.md §2.
 *
 * Field sponsorship (is_sponsored...sponsored_end_date) KHÔNG mang validation attribute — mọi
 * field khác của DTO này cũng không có, vì toàn bộ codebase chỉ gọi Data::from() (hydrate thuần),
 * không bao giờ gọi Data::validate(); validate 100% nằm ở ArticleAdminController::validated()
 * (spec/dac-ta-ky-thuat-bai-viet-tai-tro.md §6.1).
 */
class ArticleData extends Data
{
    public function __construct(
        public readonly ArticleFormat $format = ArticleFormat::Article,
        /** Chỉ có ý nghĩa khi format = redirect — NULL ở mọi format khác (Create/UpdateArticleAction tự null-out). */
        public readonly ?string $redirect_url = null,
        /**
         * spec/Media_Library_Technical_Specification.md §8 — UUID media FilePond (collection
         * `cover`) chờ gắn vào article vừa tạo — CHỈ dùng ở luồng tạo mới (create form, chưa có
         * article.id để attach trực tiếp). Form sửa gắn ảnh thẳng qua context header, không qua
         * field này (xem CreateArticleAction/UpdateArticleAction).
         */
        public readonly ?string $cover_media_uuid = null,
        public readonly bool $is_featured = false,
        public readonly ?string $main_locale = null,

        /** @var int[] */
        public readonly array $category_ids = [],
        public readonly ?int $is_primary_category_id = null,

        /** Tên tag, phân tách bởi dấu phẩy — tự tạo tag chưa tồn tại (cross-cutting, không cần UI quản lý riêng). */
        public readonly ?string $tags = null,

        /**
         * spec/Province_Showcase_Technical_Specification.md §3.2.1/§6.3 — tuỳ chọn, không bắt
         * buộc validate — không phá luồng viết bài hiện tại khi tác giả chưa chọn tỉnh.
         * Chỉ lưu mã, không denormalize tên (province_name/ward_name đã bỏ khỏi post_articles) —
         * tên tra trực tiếp từ Province/Ward lúc hiển thị.
         */
        public readonly ?string $province_code = null,
        public readonly ?string $ward_code = null,

        /**
         * spec/Province_Showcase_Technical_Specification.md §3.4.1 — sản phẩm OCOP liên quan,
         * chỉ có ở form sửa bài viết (create form không có multi-select này).
         *
         * @var int[]
         */
        public readonly array $ocop_product_ids = [],

        public readonly bool $is_sponsored = false,
        public readonly ?string $sponsor_name = null,
        public readonly ?string $sponsor_logo_url = null,
        public readonly ?SponsorLabel $sponsor_label = null,
        public readonly ?string $campaign_code = null,
        public readonly ?string $sponsored_start_date = null,
        public readonly ?string $sponsored_end_date = null,
    ) {}
}
