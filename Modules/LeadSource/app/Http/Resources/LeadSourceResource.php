<?php

namespace Modules\LeadSource\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class LeadSourceResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id'        => $this->id,
            'code'      => $this->code,
            'label'     => $this->label,
            'icon'      => $this->icon,
            'color'     => $this->color,
            'sort_order' => $this->sort_order,
            'is_active' => $this->is_active,
            'is_global' => $this->is_global,
        ];
    }
}
