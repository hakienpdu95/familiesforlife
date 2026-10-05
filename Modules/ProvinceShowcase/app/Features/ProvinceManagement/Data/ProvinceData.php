<?php

namespace Modules\ProvinceShowcase\Features\ProvinceManagement\Data;

use Spatie\LaravelData\Data;

class ProvinceData extends Data
{
    public function __construct(
        public readonly string $short_name,
        public readonly ?int $order_column = null,
        public readonly bool $is_active = true,
        public readonly bool $is_featured = false,
        public readonly ?string $slogan = null,
        public readonly ?string $description = null,
        public readonly ?string $tvc_video_url = null,
        public readonly ?string $vr360_map_url = null,
        public readonly array $highlight_tags = [],
        public readonly bool $remove_cover_image = false,
        public readonly bool $remove_logo = false,
    ) {}
}
