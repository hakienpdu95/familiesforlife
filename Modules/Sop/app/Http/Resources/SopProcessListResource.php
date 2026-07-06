<?php

namespace Modules\Sop\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Modules\Sop\Enums\SopStatus;

class SopProcessListResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        $status = $this->status;
        $type   = $this->type;

        return [
            'id'              => $this->id,
            'uuid'            => $this->uuid,
            'code'            => $this->code,
            'title'           => $this->title,
            'status'          => $status instanceof SopStatus ? $status->value : $status,
            'status_label'    => $status instanceof SopStatus ? $status->label() : $status,
            'status_badge'    => $status instanceof SopStatus ? $status->badgeClass() : 'badge-ghost',
            'type'            => $type?->value,
            'type_label'      => $type?->label(),
            'version'         => $this->version,
            'owner_name'      => $this->owner?->name,
            'department_name' => $this->department?->name,
            'branch_name'     => $this->branch?->name,
            'effective_date'  => $this->effective_date?->format('d/m/Y'),
            'expired_date'    => $this->expired_date?->format('d/m/Y'),
            'created_at'      => $this->created_at?->format('d/m/Y'),
            'show_url'        => route('backend.sop.show', $this->uuid),
            'edit_url'        => route('backend.sop.edit', $this->uuid),
            'delete_url'      => route('backend.sop.destroy', $this->uuid),
        ];
    }
}
