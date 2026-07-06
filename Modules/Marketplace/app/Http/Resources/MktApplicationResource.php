<?php
namespace Modules\Marketplace\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class MktApplicationResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id'              => $this->uuid,
            'status'          => $this->status?->value,
            'status_label'    => $this->status?->label(),
            'cover_letter'    => $this->cover_letter,
            'expected_salary' => $this->expected_salary,
            'available_from'  => $this->available_from?->toDateString(),
            'portfolio_url'   => $this->portfolio_url,
            'import_status'   => $this->import_status?->value,
            'viewed_at'       => $this->viewed_at?->toISOString(),
            'applied_at'      => $this->applied_at?->toISOString(),
            'listing'         => $this->whenLoaded('listing', fn() => [
                'id'              => $this->listing->uuid,
                'title'           => $this->listing->title,
                'slug'            => $this->listing->slug,
                'work_type'       => $this->listing->work_type?->value,
                'employment_type' => $this->listing->employment_type?->value,
                'org_name'        => $this->listing->organization?->name ?? 'Cá nhân',
                'org_logo'        => $this->listing->organization?->logo_path,
            ]),
            'applicant'       => $this->whenLoaded('applicant', fn() => [
                'id'               => $this->applicant->uuid,
                'display_name'     => $this->applicant->display_name,
                'headline'         => $this->applicant->headline,
                'avg_rating'       => $this->applicant->avg_rating,
                'years_experience' => $this->applicant->years_experience,
                'availability'     => $this->applicant->availability?->value,
                'email'            => $this->applicant->email,
            ]),
        ];
    }
}
