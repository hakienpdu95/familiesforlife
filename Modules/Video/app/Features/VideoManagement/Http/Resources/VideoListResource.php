<?php

namespace Modules\Video\Features\VideoManagement\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/** Tabulator row cho dashboard/videos/items — xem VideoApiController. */
class VideoListResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        $user = $request->user();

        return [
            'id'            => $this->id,
            'uuid'          => $this->uuid,
            'name'          => $this->name,
            'thumbnail_url'    => $this->thumbnail_url,
            'thumbnail_hd_url' => $this->thumbnail_hd_url,
            'video_url'     => $this->video_url,
            'is_active'     => (bool) $this->is_active,
            'sort_order'    => $this->sort_order,
            'created_at'    => $this->created_at?->format('d/m/Y H:i'),

            'edit_url'          => route('backend.video.items.edit', $this->resource),
            'delete_url'        => route('backend.video.items.destroy', $this->resource),
            'toggle_active_url' => route('backend.video.items.toggle-active', $this->resource),

            'can_update' => $user?->can('update', $this->resource) ?? false,
            'can_delete' => $user?->can('delete', $this->resource) ?? false,
        ];
    }
}
