<?php

namespace Modules\LeadPipelineStage\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class LeadPipelineStageResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id'          => $this->id,
            'code'        => $this->code,
            'label'       => $this->label,
            'color'       => $this->color,
            'sort_order'  => $this->sort_order,
            'probability' => $this->probability,
            'is_won'      => $this->is_won,
            'is_lost'     => $this->is_lost,
            'is_active'   => $this->is_active,
            'is_global'   => $this->is_global,
        ];
    }
}
