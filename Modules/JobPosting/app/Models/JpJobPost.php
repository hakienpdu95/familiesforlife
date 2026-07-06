<?php

namespace Modules\JobPosting\Models;

use App\Foundation\Models\TenantAwareModel;
use App\Models\User;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Modules\Department\Models\Department;
use Modules\JobPosting\Enums\EducationLevel;
use Modules\JobPosting\Enums\EmploymentType;
use Modules\JobPosting\Enums\ExperienceLevel;
use Modules\JobPosting\Enums\Industry;
use Modules\JobPosting\Enums\JobPostStatus;
use Modules\JobPosting\Enums\MktSyncStatus;
use Modules\JobPosting\Enums\SalaryType;
use Modules\JobPosting\Enums\Visibility;
use Modules\JobPosting\Enums\WorkArrangement;
use Modules\JobTitle\Models\JobTitle;

class JpJobPost extends TenantAwareModel
{
    use SoftDeletes;

    protected $table = 'jp_job_posts';

    protected $fillable = [
        'uuid',
        'organization_id',
        'department_id',
        'job_title_id',
        'created_by',
        'owner_id',
        'reviewed_by',
        'updated_by',
        'title',
        'code',
        'slug',
        'status',
        'visibility',
        'employment_type',
        'work_arrangement',
        'experience_level',
        'industry',
        'headcount',
        'hired_count',
        'city',
        'province',
        'country',
        'address_detail',
        'is_remote_allowed',
        'remote_countries',
        'summary',
        'description',
        'responsibilities',
        'requirements',
        'nice_to_have',
        'what_you_will_learn',
        'about_company',
        'min_experience_years',
        'max_experience_years',
        'education_level',
        'education_field',
        'certifications_required',
        'salary_type',
        'salary_min',
        'salary_max',
        'salary_currency',
        'salary_is_negotiable',
        'salary_is_visible',
        'salary_note',
        'probation_duration_days',
        'probation_salary_pct',
        'published_at',
        'expire_at',
        'closed_at',
        'application_email',
        'application_url',
        'allow_direct_apply',
        'require_cover_letter',
        'require_portfolio',
        'publish_to_marketplace',
        'publish_to_career_page',
        'mkt_listing_id',
        'mkt_sync_status',
        'view_count',
        'application_count',
        'share_count',
        'tags',
        'seo_title',
        'seo_description',
    ];

    protected $casts = [
        'status'               => JobPostStatus::class,
        'visibility'           => Visibility::class,
        'employment_type'      => EmploymentType::class,
        'work_arrangement'     => WorkArrangement::class,
        'experience_level'     => ExperienceLevel::class,
        'industry'             => Industry::class,
        'salary_type'          => SalaryType::class,
        'education_level'      => EducationLevel::class,
        'mkt_sync_status'      => MktSyncStatus::class,
        'is_remote_allowed'    => 'boolean',
        'salary_is_negotiable' => 'boolean',
        'salary_is_visible'    => 'boolean',
        'allow_direct_apply'   => 'boolean',
        'require_cover_letter' => 'boolean',
        'require_portfolio'    => 'boolean',
        'publish_to_marketplace' => 'boolean',
        'publish_to_career_page' => 'boolean',
        'salary_min'           => 'decimal:2',
        'salary_max'           => 'decimal:2',
        'published_at'         => 'datetime',
        'expire_at'            => 'datetime',
        'closed_at'            => 'datetime',
    ];

    // ── Relationships ─────────────────────────────────────────────────────────

    public function department(): BelongsTo
    {
        return $this->belongsTo(Department::class);
    }

    public function jobTitle(): BelongsTo
    {
        return $this->belongsTo(JobTitle::class);
    }

    public function createdBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function owner(): BelongsTo
    {
        return $this->belongsTo(User::class, 'owner_id');
    }

    public function reviewedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'reviewed_by');
    }

    public function updatedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'updated_by');
    }

    public function histories(): HasMany
    {
        return $this->hasMany(JpJobPostHistory::class, 'job_post_id')->orderByDesc('created_at');
    }

    public function skills(): HasMany
    {
        return $this->hasMany(JpJobPostSkill::class, 'job_post_id')->orderBy('sort_order');
    }

    public function benefits(): HasMany
    {
        return $this->hasMany(JpJobPostBenefit::class, 'job_post_id')->orderBy('sort_order');
    }

    public function screeningQuestions(): HasMany
    {
        return $this->hasMany(JpScreeningQuestion::class, 'job_post_id')->orderBy('sort_order');
    }

    public function stats(): HasMany
    {
        return $this->hasMany(JpJobPostStat::class, 'job_post_id');
    }
}
