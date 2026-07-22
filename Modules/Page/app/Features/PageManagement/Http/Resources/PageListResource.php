<?php

namespace Modules\Page\Features\PageManagement\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Modules\Page\Enums\PageStatus;

/** Tabulator row cho dashboard/pages/items — xem PageApiController. */
class PageListResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        $user = $request->user();
        $isPublished = $this->status === PageStatus::Published;

        return [
            'id'          => $this->id,
            'title'       => $this->title,
            'slug'        => $this->slug,
            'is_system'   => (bool) $this->is_system,

            'status_value' => $this->status->value,
            'status_label' => $this->status->label(),
            'is_published' => $isPublished,

            'updated_at' => $this->updated_at->format('d/m/Y H:i'),

            'edit_url'      => route('backend.page.items.edit', $this->resource),
            'destroy_url'   => route('backend.page.items.destroy', $this->resource),
            'publish_url'   => route('backend.page.items.publish', $this->resource),
            'unpublish_url' => route('backend.page.items.unpublish', $this->resource),

            'can_update' => $user?->can('update', $this->resource) ?? false,
            // is_system — không cho xoá kể cả khi có quyền (§3.3 spec Page) — chặn NGAY ở đây,
            // không phải chỉ ẩn nút rồi vẫn cho gọi API xoá thành công.
            'can_delete' => ! $this->is_system && ($user?->can('delete', $this->resource) ?? false),
        ];
    }
}
