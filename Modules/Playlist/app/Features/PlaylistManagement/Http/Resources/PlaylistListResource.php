<?php

namespace Modules\Playlist\Features\PlaylistManagement\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/** Tabulator row cho dashboard/playlists/items — xem PlaylistApiController. */
class PlaylistListResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        $user = $request->user();

        return [
            'id' => $this->id,
            'uuid' => $this->uuid,
            'name' => $this->name,
            'slug' => $this->slug,
            'thumbnail_url' => $this->effective_cover_image_url,
            'items_count' => $this->items_count ?? $this->items->count(),
            'is_active' => (bool) $this->is_active,
            'sort_order' => $this->sort_order,
            'created_at' => $this->created_at?->format('d/m/Y H:i'),

            'public_url' => route('playlist.public.show', $this->resource),
            'edit_url' => route('backend.playlist.items.edit', $this->resource),
            'delete_url' => route('backend.playlist.items.destroy', $this->resource),
            'toggle_active_url' => route('backend.playlist.items.toggle-active', $this->resource),

            'can_update' => $user?->can('update', $this->resource) ?? false,
            'can_delete' => $user?->can('delete', $this->resource) ?? false,
        ];
    }
}
