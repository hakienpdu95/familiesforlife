<?php

namespace Modules\Newsletter\Features\BroadcastSending\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/** Tabulator row cho dashboard/newsletter/broadcast/logs — xem BroadcastLogApiController. */
class BroadcastLogListResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id'                  => $this->id,
            'subject'             => $this->subject,
            'scheduled_at'        => $this->scheduled_at?->format('d/m/Y H:i'),
            'created_at'          => $this->created_at->format('d/m/Y H:i'),
            'sent_by_name'        => $this->sentBy?->name,
            'resend_broadcast_id' => $this->resend_broadcast_id,
        ];
    }
}
