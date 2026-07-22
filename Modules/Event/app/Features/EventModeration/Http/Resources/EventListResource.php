<?php

namespace Modules\Event\Features\EventModeration\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Modules\Event\Enums\EventStatus;

/** Tabulator row cho dashboard/events — xem EventApiController. */
class EventListResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        $user = $request->user();

        return [
            'id'             => $this->id,
            'title'          => $this->title,
            'slug'           => $this->slug,
            'category_name'  => $this->category?->name,
            'category_color' => $this->category?->color_hex,

            'start_date' => $this->start_date?->format('d/m/Y'),
            'end_date'   => $this->end_date?->format('d/m/Y'),

            'status_value' => $this->status->value,
            'status_label' => $this->status->label(),
            'status_badge' => $this->status->badgeClass(),

            // Cùng điều kiện transition đang dùng ở index.blade.php cũ — server chốt lần cuối
            // trong từng Action (canTransitionTo), đây chỉ quyết định HIỂN THỊ nút nào.
            'can_approve' => $user?->can('approve', $this->resource) && $this->status->canTransitionTo(EventStatus::Approved),
            'can_reject'  => $user?->can('reject', $this->resource) && $this->status->canTransitionTo(EventStatus::Rejected),
            'can_publish' => $user?->can('publish', $this->resource) && $this->status->canTransitionTo(EventStatus::Published),
            'can_archive' => $user?->can('archive', $this->resource) && $this->status->canTransitionTo(EventStatus::Archived),
            'can_update'  => $user?->can('update', $this->resource) ?? false,

            'edit_url'    => route('backend.event.edit', $this->resource),
            'approve_url' => route('backend.event.approve', $this->resource),
            'reject_url'  => route('backend.event.reject', $this->resource),
            'publish_url' => route('backend.event.publish', $this->resource),
            'archive_url' => route('backend.event.archive', $this->resource),
        ];
    }
}
