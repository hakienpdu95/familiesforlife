<?php

namespace Modules\Post\Features\ArticleAuthoring\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/** Tabulator row cho dashboard/posts/articles — xem ArticleApiController. */
class ArticleListResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        $main = $this->mainTranslation();
        $user = $request->user();

        $canManage = $user?->can('post_article.edit')
            && ($this->created_by === $user->id || $user->can('post_article.publish'));

        return [
            'id'          => $this->id,
            'title'       => $main?->title ?? "(chưa có bản dịch {$this->main_locale})",
            'category'    => $this->categories->pluck('name')->implode(', ') ?: null,
            'format'      => $this->format->value,
            'format_label' => $this->format->label(),

            'status_value' => $main?->status?->value,
            'status_label' => $main?->status?->label(),
            'status_badge' => $main?->status?->badgeClass(),

            'is_sponsored'    => (bool) $this->is_sponsored,
            'sponsor_badge'   => $this->sponsor_label?->badgeClass() ?? 'badge-warning',

            'created_by_name' => $this->createdBy?->name,
            'created_at'      => $this->created_at?->format('d/m/Y'),

            'is_redirect' => $this->isRedirect(),

            'show_url'   => route('backend.post.articles.show', $this->resource),
            'edit_url'   => route('backend.post.articles.edit', $this->resource),
            'destroy_url' => route('backend.post.articles.destroy', $this->resource),
            'clicks_url' => $this->isRedirect() ? route('backend.post.articles.clicks', $this->resource) : null,

            'can_manage' => (bool) $canManage,
        ];
    }
}
