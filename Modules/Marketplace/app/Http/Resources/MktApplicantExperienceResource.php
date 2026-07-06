<?php
namespace Modules\Marketplace\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class MktApplicantExperienceResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id'           => $this->uuid,
            'company_name' => $this->company_name,
            'title'        => $this->title,
            'description'  => $this->description,
            'start_month'  => $this->start_month,
            'start_year'   => $this->start_year,
            'end_month'    => $this->end_month,
            'end_year'     => $this->end_year,
            'is_current'   => $this->is_current,
            'sort_order'   => $this->sort_order,
        ];
    }
}
