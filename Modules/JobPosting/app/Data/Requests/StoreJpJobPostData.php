<?php

namespace Modules\JobPosting\Data\Requests;

use Spatie\LaravelData\Data;

class StoreJpJobPostData extends Data
{
    public static function rules(): array
    {
        return [
            'organization_id' => ['required', 'integer', 'exists:organizations,id'],
        ];
    }

    public function __construct(
        public readonly int     $organization_id,
        public readonly string  $title,
        public readonly string  $description,
        public readonly string  $requirements,
        public readonly string  $employment_type,
        public readonly string  $work_arrangement,
        public readonly string  $experience_level,
        public readonly string  $industry,
        public readonly string  $salary_type,
        public readonly int     $headcount,
        public readonly string  $country,
        public readonly string  $visibility,

        public readonly ?int    $department_id          = null,
        public readonly ?int    $job_title_id           = null,
        public readonly ?int    $owner_id               = null,
        public readonly ?string $summary                = null,
        public readonly ?string $responsibilities       = null,
        public readonly ?string $nice_to_have           = null,
        public readonly ?string $what_you_will_learn    = null,
        public readonly ?string $about_company          = null,
        public readonly ?string $city                   = null,
        public readonly ?string $province               = null,
        public readonly ?string $address_detail         = null,
        public readonly bool    $is_remote_allowed      = false,
        public readonly ?string $remote_countries       = null,
        public readonly ?int    $min_experience_years   = null,
        public readonly ?int    $max_experience_years   = null,
        public readonly ?string $education_level        = null,
        public readonly ?string $education_field        = null,
        public readonly ?string $certifications_required= null,
        public readonly ?float  $salary_min             = null,
        public readonly ?float  $salary_max             = null,
        public readonly string  $salary_currency        = 'VND',
        public readonly bool    $salary_is_negotiable   = false,
        public readonly bool    $salary_is_visible      = true,
        public readonly ?string $salary_note            = null,
        public readonly ?int    $probation_duration_days= null,
        public readonly ?int    $probation_salary_pct   = null,
        public readonly ?string $expire_at              = null,
        public readonly ?string $application_email      = null,
        public readonly ?string $application_url        = null,
        public readonly bool    $allow_direct_apply     = true,
        public readonly bool    $require_cover_letter   = false,
        public readonly bool    $require_portfolio      = false,
        public readonly bool    $publish_to_marketplace = false,
        public readonly bool    $publish_to_career_page = true,
        public readonly ?string $tags                   = null,
        public readonly ?string $seo_title              = null,
        public readonly ?string $seo_description        = null,
    ) {}
}
