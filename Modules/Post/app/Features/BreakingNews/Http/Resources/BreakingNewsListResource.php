<?php

namespace Modules\Post\Features\BreakingNews\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/** Tabulator row cho dashboard/breaking-news/items — xem BreakingNewsApiController. */
class BreakingNewsListResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        $user        = $request->user();
        $translation = $this->article?->translations->first();

        return [
            'id'                => $this->id,
            'badge_label'       => $this->displayBadgeLabel(),
            'headline'          => $this->headline_override ?: (string) $translation?->title,
            'article_title'     => (string) $translation?->title,
            'has_override'      => $this->headline_override !== null,

            'starts_at' => format_datetime($this->starts_at),
            'ends_at'   => format_datetime($this->ends_at),

            'is_active'  => (bool) $this->is_active,
            'is_running' => $this->isCurrentlyBreaking(),

            'edit_url'    => route('backend.post.breaking-news.items.edit', $this->resource),
            'destroy_url' => route('backend.post.breaking-news.items.destroy', $this->resource),

            'can_update' => $user?->can('update', $this->resource) ?? false,
            'can_delete' => $user?->can('delete', $this->resource) ?? false,
        ];
    }
}
