<?php

namespace Modules\Post\Features\TagManagement\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/** Tabulator row cho dashboard/posts/tags — xem TagApiController. */
class TagListResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        $user = $request->user();

        return [
            'id'             => $this->id,
            'name'           => $this->name,
            'slug'           => $this->slug,
            'articles_count' => $this->articles_count,

            'edit_url'    => route('backend.post.tags.edit', $this->resource),
            'destroy_url' => route('backend.post.tags.destroy', $this->resource),
            'merge_url'   => route('backend.post.tags.merge', $this->resource),

            'can_update' => $user?->can('update', $this->resource) ?? false,
            'can_delete' => $user?->can('delete', $this->resource) ?? false,
        ];
    }
}
