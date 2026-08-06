<?php

namespace Modules\N8n\Features\LogViewing\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Modules\N8n\Models\N8nInboundLog;

/** @mixin N8nInboundLog */
class N8nInboundLogListResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'connection_name' => $this->connection?->name,
            'connection_deleted' => $this->connection?->deleted_at !== null,
            'ip_address' => $this->ip_address,
            'signature_valid' => $this->signature_valid,
            'http_status_returned' => $this->http_status_returned,
            'event_name' => $this->event_name,
            'listener_count' => $this->listener_count,
            'payload_excerpt' => $this->payload_excerpt,
            'error_message' => $this->error_message,
            'received_at' => $this->received_at?->toIso8601String(),
        ];
    }
}
