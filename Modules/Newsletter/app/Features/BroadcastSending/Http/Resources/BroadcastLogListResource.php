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
            'scheduled_at'        => format_datetime($this->scheduled_at),
            'created_at'          => format_datetime($this->created_at),
            'sent_by_name'        => $this->sentBy?->name,
            'resend_broadcast_id' => $this->resend_broadcast_id,
        ];
    }
}
