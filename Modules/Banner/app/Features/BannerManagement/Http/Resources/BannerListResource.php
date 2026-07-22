<?php

namespace Modules\Banner\Features\BannerManagement\Http\Resources;

use App\Models\Province;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Modules\Banner\Models\Banner;
use Modules\Post\Models\PostCategory;

/** Tabulator row cho dashboard/banners/items — xem BannerApiController. */
class BannerListResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        $user = $request->user();

        $isRunning = $this->is_active
            && (! $this->start_date || $this->start_date->lte(now()))
            && (! $this->end_date || $this->end_date->gte(now()));

        return [
            'id'          => $this->id,
            'image_url'   => $this->getFirstMediaUrl('banner', 'medium'),
            'placement'   => $this->placement,
            'placement_label' => Banner::getPlacementLabel($this->placement) ?? $this->placement,
            'title'       => $this->title,

            'target_label' => $this->targetLabel(),
            'target_kind'  => $this->target_type?->value, // null|category|province — dùng để chọn màu badge

            'start_date' => $this->start_date?->format('d/m/Y'),
            'end_date'   => $this->end_date?->format('d/m/Y'),

            'click_count' => (int) $this->click_count,
            'is_active'   => (bool) $this->is_active,
            'is_running'  => $isRunning,

            'edit_url'    => route('backend.banner.items.edit', $this->resource),
            'destroy_url' => route('backend.banner.items.destroy', $this->resource),

            'can_update' => $user?->can('update', $this->resource) ?? false,
            'can_delete' => $user?->can('delete', $this->resource) ?? false,
        ];
    }

    /** Cùng logic đang có ở index.blade.php cũ — tra tên category/tỉnh theo target_value. */
    private function targetLabel(): ?string
    {
        if ($this->target_type === null) {
            return 'Toàn site';
        }

        if ($this->target_type->value === 'category') {
            $name = $this->target_value ? PostCategory::where('slug', $this->target_value)->value('name') : null;

            return $name ? "Danh mục: {$name}" : 'Danh mục: (đã xoá)';
        }

        if ($this->target_type->value === 'province') {
            $name = $this->target_value ? Province::where('province_code', $this->target_value)->value('name') : null;

            return $name ? "Tỉnh/thành: {$name}" : 'Tỉnh/thành: (không rõ)';
        }

        return null;
    }
}
