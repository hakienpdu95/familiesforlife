<?php
namespace Modules\Marketplace\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class MktApplicantPortfolioResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id'             => $this->uuid,
            'title'          => $this->title,
            'description'    => $this->description,
            'project_url'    => $this->project_url,
            'thumbnail_url'  => $this->thumbnail_url,
            'tech_stack'     => $this->tech_stack,
            'completed_year' => $this->completed_year,
            'sort_order'     => $this->sort_order,
        ];
    }
}
