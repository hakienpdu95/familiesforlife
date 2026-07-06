<?php

namespace Modules\JobPosting\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Str;
use Modules\JobPosting\Enums\ProficiencyLevel;
use Modules\JobPosting\Enums\RequirementLevel;

class JpJobPostSkill extends Model
{
    public $timestamps = false;

    protected $table = 'jp_job_post_skills';

    protected $fillable = [
        'uuid',
        'job_post_id',
        'skill_id',
        'skill_name',
        'requirement_level',
        'proficiency',
        'min_years',
        'sort_order',
    ];

    protected $casts = [
        'requirement_level' => RequirementLevel::class,
        'proficiency'       => ProficiencyLevel::class,
    ];

    protected static function booted(): void
    {
        static::creating(function (self $model) {
            $model->uuid ??= Str::uuid()->toString();
        });
    }

    public function jobPost(): BelongsTo
    {
        return $this->belongsTo(JpJobPost::class, 'job_post_id');
    }

    public function skill(): BelongsTo
    {
        return $this->belongsTo(JpSkillMaster::class, 'skill_id');
    }
}
