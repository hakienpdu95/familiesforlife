<?php

namespace Modules\JobPosting\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class JpJobPostPublicResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        /** @var \Modules\JobPosting\Models\JpJobPost $this */
        $salary = null;
        if ($this->salary_is_visible && ($this->salary_min || $this->salary_max)) {
            $salary = [
                'type'         => $this->salary_type?->value,
                'type_label'   => $this->salary_type?->label(),
                'min'          => $this->salary_min,
                'max'          => $this->salary_max,
                'currency'     => $this->salary_currency,
                'is_negotiable'=> $this->salary_is_negotiable,
                'note'         => $this->salary_note,
            ];
        } elseif ($this->salary_is_negotiable) {
            $salary = ['is_negotiable' => true];
        }

        return [
            'uuid'                  => $this->uuid,
            'slug'                  => $this->slug,
            'code'                  => $this->code,
            'title'                 => $this->title,
            'summary'               => $this->summary,
            'description'           => $this->description,
            'responsibilities'      => $this->responsibilities,
            'requirements'          => $this->requirements,
            'nice_to_have'          => $this->nice_to_have,
            'what_you_will_learn'   => $this->what_you_will_learn,
            'about_company'         => $this->about_company,

            'department'            => $this->department?->name,
            'employment_type'       => $this->employment_type?->value,
            'employment_type_label' => $this->employment_type?->label(),
            'work_arrangement'      => $this->work_arrangement?->value,
            'work_arrangement_label'=> $this->work_arrangement?->label(),
            'experience_level'      => $this->experience_level?->value,
            'experience_level_label'=> $this->experience_level?->label(),
            'industry'              => $this->industry?->value,
            'industry_label'        => $this->industry?->label(),
            'headcount'             => $this->headcount,

            'city'                  => $this->city,
            'province'              => $this->province,
            'country'               => $this->country,
            'is_remote_allowed'     => $this->is_remote_allowed,

            'min_experience_years'  => $this->min_experience_years,
            'max_experience_years'  => $this->max_experience_years,
            'education_level'       => $this->education_level?->value,
            'education_level_label' => $this->education_level?->label(),
            'education_field'       => $this->education_field,

            'salary'                => $salary,

            'allow_direct_apply'    => $this->allow_direct_apply,
            'require_cover_letter'  => $this->require_cover_letter,
            'require_portfolio'     => $this->require_portfolio,
            'application_url'       => $this->application_url,
            'application_email'     => $this->application_email,

            'published_at'          => $this->published_at?->toIso8601String(),
            'expire_at'             => $this->expire_at?->toIso8601String(),

            'skills'   => $this->whenLoaded('skills', fn () =>
                $this->skills->map(fn ($s) => [
                    'name'              => $s->skill_name,
                    'requirement_level' => $s->requirement_level?->value,
                    'proficiency'       => $s->proficiency?->value,
                    'min_years'         => $s->min_years,
                ])
            ),
            'benefits' => $this->whenLoaded('benefits', fn () =>
                $this->benefits->map(fn ($b) => [
                    'name'        => $b->benefit_name,
                    'description' => $b->description,
                    'icon'        => $b->icon ?? null,
                ])
            ),

            'tags'            => $this->tags,
            'seo_title'       => $this->seo_title,
            'seo_description' => $this->seo_description,
        ];
    }
}
