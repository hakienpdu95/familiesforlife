<?php

namespace Modules\N8n\Features\LogViewing\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Modules\N8n\Models\N8nOutboundLog;

/** @mixin N8nOutboundLog */
class N8nOutboundLogListResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'connection_name' => $this->connection?->name,
            'connection_deleted' => $this->connection?->deleted_at !== null,
            'event_name' => $this->event_name,
            'caller' => $this->caller,
            'success' => $this->success,
            'http_status' => $this->http_status,
            'duration_ms' => $this->duration_ms,
            'error_message' => $this->error_message,
            'payload_excerpt' => $this->payload_excerpt,
            'requested_at' => $this->requested_at?->toIso8601String(),
        ];
    }
}
