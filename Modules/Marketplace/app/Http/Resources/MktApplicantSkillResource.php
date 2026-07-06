<?php
namespace Modules\Marketplace\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class MktApplicantSkillResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id'                => $this->uuid,
            'skill_name'        => $this->skill_name,
            'proficiency_level' => $this->proficiency_level?->value,
            'proficiency_label' => $this->proficiency_level?->label(),
            'years_used'        => $this->years_used,
            'sort_order'        => $this->sort_order,
        ];
    }
}
