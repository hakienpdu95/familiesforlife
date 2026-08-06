<?php

namespace Modules\N8n\Features\ConnectionManagement\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Modules\N8n\Models\N8nConnection;

/**
 * spec/N8n_Integration_Technical_Specification.md §3.2 — KHÔNG BAO GIỜ trả giá trị plaintext
 * đầy đủ của inbound_token/inbound_secret/outbound_secret qua Tabulator JSON — chỉ placeholder
 * đã che.
 *
 * @mixin N8nConnection
 */
class N8nConnectionListResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        $user = $request->user();
        $canManage = $user?->can('manage-n8n') ?? false;

        return [
            'id' => $this->id,
            'uuid' => $this->uuid,
            'name' => $this->name,
            'purpose_note' => $this->purpose_note,
            'inbound_enabled' => $this->inbound_enabled,
            'outbound_enabled' => $this->outbound_enabled,
            'inbound_token_masked' => $this->maskedInboundToken(),
            'inbound_secret_masked' => $this->maskedInboundSecret(),
            'outbound_secret_masked' => $this->maskedOutboundSecret(),
            'sends_unsigned_outbound' => $this->sendsUnsignedOutbound(),
            'allowed_ip_cidrs' => $this->allowed_ip_cidrs,
            'rate_limit_per_minute' => $this->rate_limit_per_minute,
            'last_inbound_at' => $this->last_inbound_at?->format('d/m/Y H:i'),
            'last_outbound_at' => $this->last_outbound_at?->format('d/m/Y H:i'),
            'deleted_at' => $this->deleted_at?->format('d/m/Y H:i'),
            'created_at' => $this->created_at?->format('d/m/Y H:i'),

            // edit/destroy chỉ áp dụng cho connection còn "sống" — route model binding mặc định
            // (SoftDeletes global scope) không resolve được connection đã xoá mềm; restore là
            // hành động DUY NHẤT khả dụng cho hàng đã trashed (§2.5/§7).
            'edit_url' => ($canManage && ! $this->trashed()) ? route('backend.n8n.connections.edit', $this->resource) : null,
            'delete_url' => ($canManage && ! $this->trashed()) ? route('backend.n8n.connections.destroy', $this->resource) : null,
            'restore_url' => ($canManage && $this->trashed()) ? route('backend.n8n.connections.restore', $this->uuid) : null,

            'can_manage' => $canManage,
        ];
    }
}
