<?php

namespace Modules\JobPosting\Actions\Backend;

use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Str;
use Lorisleiva\Actions\Concerns\AsAction;
use Modules\JobPosting\Data\Requests\StoreJpJobPostData;
use Modules\JobPosting\Enums\HistoryChangeType;
use Modules\JobPosting\Enums\JobPostStatus;
use Modules\JobPosting\Events\JpJobPostCreated;
use Modules\JobPosting\Models\JpJobPost;
use Modules\JobPosting\Models\JpJobPostHistory;

class StoreJpJobPostAction
{
    use AsAction;

    public function handle(StoreJpJobPostData $data): JpJobPost
    {
        $orgId  = $data->organization_id;
        $userId = Auth::id();

        $slug = $this->generateSlug($data->title, $orgId);
        $code = $this->generateCode($orgId);

        $post = JpJobPost::create([
            'uuid'                   => Str::uuid()->toString(),
            'organization_id'        => $orgId,
            'department_id'          => $data->department_id,
            'job_title_id'           => $data->job_title_id,
            'created_by'             => $userId,
            'owner_id'               => $data->owner_id ?? $userId,
            'updated_by'             => $userId,
            'title'                  => $data->title,
            'code'                   => $code,
            'slug'                   => $slug,
            'status'                 => JobPostStatus::Draft->value,
            'visibility'             => $data->visibility,
            'employment_type'        => $data->employment_type,
            'work_arrangement'       => $data->work_arrangement,
            'experience_level'       => $data->experience_level,
            'industry'               => $data->industry,
            'headcount'              => $data->headcount,
            'city'                   => $data->city,
            'province'               => $data->province,
            'country'                => $data->country,
            'address_detail'         => $data->address_detail,
            'is_remote_allowed'      => $data->is_remote_allowed,
            'remote_countries'       => $data->remote_countries,
            'summary'                => $data->summary,
            'description'            => $data->description,
            'responsibilities'       => $data->responsibilities,
            'requirements'           => $data->requirements,
            'nice_to_have'           => $data->nice_to_have,
            'what_you_will_learn'    => $data->what_you_will_learn,
            'about_company'          => $data->about_company,
            'min_experience_years'   => $data->min_experience_years,
            'max_experience_years'   => $data->max_experience_years,
            'education_level'        => $data->education_level,
            'education_field'        => $data->education_field,
            'certifications_required'=> $data->certifications_required,
            'salary_type'            => $data->salary_type,
            'salary_min'             => $data->salary_min,
            'salary_max'             => $data->salary_max,
            'salary_currency'        => $data->salary_currency,
            'salary_is_negotiable'   => $data->salary_is_negotiable,
            'salary_is_visible'      => $data->salary_is_visible,
            'salary_note'            => $data->salary_note,
            'probation_duration_days'=> $data->probation_duration_days,
            'probation_salary_pct'   => $data->probation_salary_pct,
            'expire_at'              => $data->expire_at,
            'application_email'      => $data->application_email,
            'application_url'        => $data->application_url,
            'allow_direct_apply'     => $data->allow_direct_apply,
            'require_cover_letter'   => $data->require_cover_letter,
            'require_portfolio'      => $data->require_portfolio,
            'publish_to_marketplace' => $data->publish_to_marketplace,
            'publish_to_career_page' => $data->publish_to_career_page,
            'tags'                   => $data->tags,
            'seo_title'              => $data->seo_title,
            'seo_description'        => $data->seo_description,
        ]);

        JpJobPostHistory::create([
            'uuid'         => Str::uuid()->toString(),
            'job_post_id'  => $post->id,
            'change_type'  => HistoryChangeType::Created->value,
            'new_status'   => JobPostStatus::Draft->value,
            'changed_by'   => $userId,
        ]);

        event(new JpJobPostCreated($post));

        return $post;
    }

    private function generateSlug(string $title, int $orgId): string
    {
        $base = Str::slug($title);
        $slug = $base;
        $i    = 1;

        while (JpJobPost::withoutTenant()->where('organization_id', $orgId)->where('slug', $slug)->exists()) {
            $slug = $base . '-' . $i++;
        }

        return $slug;
    }

    private function generateCode(int $orgId): string
    {
        $year  = now()->format('Y');
        $count = JpJobPost::withoutTenant()
            ->where('organization_id', $orgId)
            ->whereYear('created_at', $year)
            ->count();

        return 'JP-' . $year . '-' . str_pad($count + 1, 4, '0', STR_PAD_LEFT);
    }
}
