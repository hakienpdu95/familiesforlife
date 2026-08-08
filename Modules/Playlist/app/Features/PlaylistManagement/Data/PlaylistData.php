<?php

namespace Modules\Playlist\Features\PlaylistManagement\Data;

use Spatie\LaravelData\Data;

/**
 * spec/Playlist_Technical_Specification.md §6.2 — Validate thật nằm ở
 * PlaylistAdminController::validated(). DTO này chỉ hydrate dữ liệu đã validate, cùng nguyên tắc
 * VideoData/BannerData.
 */
class PlaylistData extends Data
{
    public function __construct(
        public readonly string $name,
        public readonly string $slug,
        public readonly ?string $description,
        public readonly ?string $cover_image_url,
        public readonly ?string $meta_title,
        public readonly ?string $meta_description,
        public readonly int $sort_order = 0,
        public readonly bool $is_active = true,
    ) {}
}
