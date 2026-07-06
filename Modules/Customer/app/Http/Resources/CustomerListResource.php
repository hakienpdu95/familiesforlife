<?php
namespace Modules\Customer\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class CustomerListResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        $type  = $this->customer_type;
        $stage = $this->lifecycle_stage;

        return [
            'id'               => $this->id,
            'display_name'     => $this->display_name,
            'type_value'       => $type?->value,
            'type_label'       => $type?->label(),
            'type_badge'       => $type?->badgeClass(),
            'primary_email'    => $this->primary_email,
            'primary_phone'    => $this->primary_phone,
            'company_name'     => $this->company_name,
            'stage_value'      => $stage?->value,
            'stage_label'      => $stage?->label(),
            'stage_badge'      => $stage?->badgeClass(),
            'source_label'     => $this->source?->label,
            'assignee_name'    => $this->assignee?->name,
            'organization_name' => $this->whenLoaded('organization', fn () => $this->organization?->name),
            'province_code'    => $this->province_code,
            'last_activity_at' => $this->last_activity_at?->format('d/m/Y'),
            'activity_count'   => $this->activity_count,
            'tags'             => $this->whenLoaded('tags', fn () =>
                $this->tags->map(fn ($t) => ['id' => $t->id, 'name' => $t->name, 'color' => $t->color])
            ),
            'created_at'       => $this->created_at?->format('d/m/Y'),
            'show_url'         => route('customer.show', $this->resource),
            'edit_url'         => route('customer.edit', $this->resource),
            'delete_url'       => route('customer.destroy', $this->resource),
        ];
    }
}
