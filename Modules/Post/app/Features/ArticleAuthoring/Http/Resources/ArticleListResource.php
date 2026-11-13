<?php

namespace Modules\Post\Features\ArticleAuthoring\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Modules\Post\Enums\TranslationStatus;

/** Tabulator row cho dashboard/posts/articles — xem ArticleApiController. */
class ArticleListResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        $main = $this->mainTranslation();
        $user = $request->user();

        $canManage = $user?->can('post_article.edit')
            && ($this->created_by === $user->id || $user->can('post_article.publish'));

        $canDelete = $user && ($user->isPlatformContentEditor() || $user->isPlatformContentHead()
            || ($user->can('post_article.delete') && ($this->created_by === $user->id || $user->can('post_article.publish'))));

        $trashed = $this->trashed();

        return [
            'id' => $this->id,
            'title' => $main?->title ?? __('(chưa có bản dịch :main_locale)', ['main_locale' => $this->main_locale]),
            'category' => $this->categories->pluck('name')->implode(', ') ?: null,
            'format' => $this->format->value,
            'format_label' => $this->format->label(),

            'status_value' => $main?->status?->value,
            'status_label' => $main?->status?->label(),
            'status_badge' => $main?->status?->badgeClass(),

            'is_sponsored' => (bool) $this->is_sponsored,
            'sponsor_badge' => $this->sponsor_label?->badgeClass() ?? 'badge-warning',

            'created_by_name' => $this->createdBy?->name,
            'created_at' => format_date($this->created_at),

            'is_redirect' => $this->isRedirect(),

            'qr_code' => $this->qrIdentity?->qr_code,
            'qr_url' => $this->qrIdentity?->publicUrl(),

            'show_url' => route('backend.post.articles.show', $this->resource),
            'edit_url' => route('backend.post.articles.edit', $this->resource),
            'destroy_url' => route('backend.post.articles.destroy', $this->resource),
            'clicks_url' => $this->isRedirect() ? route('backend.post.articles.clicks', $this->resource) : null,

            'can_manage' => (bool) $canManage && ! $trashed,

            'is_trashed' => $trashed,
            'deleted_at' => format_datetime($this->deleted_at),
            'is_public' => $trashed && $main?->status === TranslationStatus::Published,
            'restore_url' => $trashed && $canDelete ? route('backend.post.articles.restore', $this->resource) : null,
        ];
    }
}
