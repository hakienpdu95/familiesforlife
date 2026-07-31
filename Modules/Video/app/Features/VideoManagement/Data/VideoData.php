<?php

namespace Modules\Video\Features\VideoManagement\Data;

use Spatie\LaravelData\Data;

/**
 * Validate thật nằm ở VideoAdminController::validated() + ResolveYoutubeVideoIdAction — DTO
 * này chỉ hydrate dữ liệu đã validate, cùng nguyên tắc BannerData.
 */
class VideoData extends Data
{
    public function __construct(
        public readonly string $name,
        public readonly ?string $description,
        public readonly ?string $video_url,
        public readonly ?string $embed_code,
        public readonly int $sort_order = 0,
        public readonly bool $is_active = true,
    ) {}
}
