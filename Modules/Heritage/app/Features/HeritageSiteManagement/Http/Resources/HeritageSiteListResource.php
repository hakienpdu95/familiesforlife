<?php

namespace Modules\Heritage\Features\HeritageSiteManagement\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Modules\Heritage\Enums\HeritageSiteStatus;

/** Tabulator row cho dashboard/heritage/sites — xem HeritageSiteApiController. */
class HeritageSiteListResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        $user = $request->user();
        $trashed = $this->trashed();

        return [
            'id' => $this->id,
            'image_url' => $this->getFirstMediaUrl('cover', 'thumb'),
            'name' => $this->name,
            'is_featured' => (bool) $this->is_featured,
            'heritage_type_label' => $this->heritage_type->label(),
            'rank_label' => $this->rank->label(),
            'province_name' => $this->province_name,

            'status_value' => $this->status->value,
            'status_label' => $this->status->label(),

            'qr_code' => $this->qrIdentity?->qr_code,
            'qr_url' => $this->qrIdentity?->publicUrl(),

            'edit_url' => route('backend.heritage.sites.edit', $this->resource),
            'destroy_url' => route('backend.heritage.sites.destroy', $this->resource),

            'can_update' => ! $trashed && ($user?->can('update', $this->resource) ?? false),
            'can_delete' => ! $trashed && ($user?->can('delete', $this->resource) ?? false),

            'is_trashed' => $trashed,
            'is_public' => $this->status === HeritageSiteStatus::Published,
            'deleted_at' => format_datetime($this->deleted_at),
            'restore_url' => $trashed && ($user?->can('restore', $this->resource) ?? false) ? route('backend.heritage.sites.restore', $this->resource) : null,
        ];
    }
}
