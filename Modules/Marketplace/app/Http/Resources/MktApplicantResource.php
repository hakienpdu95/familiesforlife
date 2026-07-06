<?php
namespace Modules\Marketplace\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class MktApplicantResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id'                   => $this->uuid,
            'display_name'         => $this->display_name,
            'slug'                 => $this->slug,
            'headline'             => $this->headline,
            'bio'                  => $this->bio,
            'location'             => $this->location,
            'avatar_url'           => $this->avatar_url,
            'website_url'          => $this->website_url,
            'linkedin_url'         => $this->linkedin_url,
            'years_experience'     => $this->years_experience,
            'availability'         => $this->availability?->value,
            'availability_label'   => $this->availability?->label(),
            'account_type'         => $this->account_type?->value,
            'is_profile_public'    => $this->is_profile_public,
            'profile_complete_pct' => $this->profile_complete_pct,
            'total_applications'   => $this->total_applications,
            'hired_count'          => $this->hired_count,
            'avg_rating'           => $this->avg_rating,
            'email'                => $this->when($this->is_email_public || $request->user('marketplace')?->id === $this->id, $this->email),
            'expected_salary_min'  => $this->when($request->user('marketplace')?->id === $this->id, $this->expected_salary_min),
            'expected_salary_max'  => $this->when($request->user('marketplace')?->id === $this->id, $this->expected_salary_max),
            'salary_currency'      => $this->salary_currency,
            'skills'               => MktApplicantSkillResource::collection($this->whenLoaded('skills')),
            'experiences'          => MktApplicantExperienceResource::collection($this->whenLoaded('experiences')),
            'portfolios'           => MktApplicantPortfolioResource::collection($this->whenLoaded('portfolios')),
            'created_at'           => $this->created_at?->toISOString(),
        ];
    }
}
