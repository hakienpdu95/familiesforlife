<?php

namespace Modules\JobPosting\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Modules\JobPosting\Enums\JobPostStatus;

class JpJobPostListResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        /** @var \Modules\JobPosting\Models\JpJobPost $this */
        $status          = $this->status;
        $employmentType  = $this->employment_type;
        $workArrangement = $this->work_arrangement;
        $experienceLevel = $this->experience_level;
        $mktSync         = $this->mkt_sync_status;

        return [
            'id'                  => $this->id,
            'uuid'                => $this->uuid,
            'code'                => $this->code,
            'title'               => $this->title,

            'status'              => $status instanceof JobPostStatus ? $status->value : $status,
            'status_label'        => $status instanceof JobPostStatus ? $status->label() : $status,
            'status_badge'        => $status instanceof JobPostStatus ? $status->badgeClass() : 'badge-ghost',

            'employment_type'     => $employmentType?->value,
            'employment_type_label' => $employmentType?->label(),

            'work_arrangement'    => $workArrangement?->value,
            'work_arrangement_label' => $workArrangement?->label(),

            'experience_level'    => $experienceLevel?->value,
            'experience_level_label' => $experienceLevel?->label(),

            'industry_label'      => $this->industry?->label(),

            'department_name'     => $this->department_name ?? null,
            'position_name'       => $this->position_name ?? null,
            'owner_name'          => $this->owner_name ?? null,

            'headcount'           => $this->headcount,
            'hired_count'         => $this->hired_count,
            'application_count'   => $this->application_count,
            'view_count'          => $this->view_count,

            'city'                => $this->city,
            'country'             => $this->country,

            'salary_min'          => $this->salary_min,
            'salary_max'          => $this->salary_max,
            'salary_currency'     => $this->salary_currency,
            'salary_is_visible'   => $this->salary_is_visible,

            'mkt_sync_status'     => $mktSync?->value,
            'mkt_sync_label'      => $mktSync?->label(),
            'publish_to_marketplace' => $this->publish_to_marketplace,

            'published_at'        => $this->published_at?->format('d/m/Y'),
            'expire_at'           => $this->expire_at?->format('d/m/Y'),
            'created_at'          => $this->created_at?->format('d/m/Y'),

            'show_url'            => route('backend.job-posts.show', $this->resource),
            'edit_url'            => route('backend.job-posts.edit', $this->resource),
            'delete_url'          => route('backend.job-posts.destroy', $this->resource),
        ];
    }
}
