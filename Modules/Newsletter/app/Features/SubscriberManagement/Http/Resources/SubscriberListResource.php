<?php

namespace Modules\Newsletter\Features\SubscriberManagement\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/** Tabulator row cho dashboard/newsletter/subscribers — xem SubscriberApiController. */
class SubscriberListResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        $user = $request->user();

        return [
            'id'            => $this->id,
            'full_name'     => $this->full_name,
            'email'         => $this->email,
            'status_value'  => $this->status->value,
            'status_label'  => $this->status->label(),
            'status_badge'  => $this->status->badgeClass(),
            'subscribed_at' => $this->subscribed_at?->format('d/m/Y H:i'),

            'destroy_url' => route('backend.newsletter.subscribers.destroy', $this->resource),
            'can_remove'  => $user?->can('removeSubscriber', $this->resource) ?? false,
        ];
    }
}
