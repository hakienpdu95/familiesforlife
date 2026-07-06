<?php

namespace Modules\Recruitment\Models;

use App\Models\User;
use App\Shared\Tenancy\TenantContext;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Str;
use Modules\Recruitment\Enums\ApplicationStatus;
use Modules\Recruitment\Enums\CandidateSource;

class RcApplication extends Model
{
    protected $table = 'rc_applications';

    const CREATED_AT = 'applied_at';

    protected $fillable = [
        'uuid',
        'candidate_id',
        'current_stage_id',
        'org_id',
        'jp_job_post_id',
        'mkt_application_id',
        'status',
        'apply_source',
        'cover_letter',
        'expected_salary',
        'notice_period_days',
        'is_disqualified',
        'disqualify_reason',
        'assigned_to',
        'rejection_reason',
        'applied_at',
    ];

    protected $casts = [
        'status'          => ApplicationStatus::class,
        'apply_source'    => CandidateSource::class,
        'is_disqualified' => 'boolean',
        'applied_at'      => 'datetime',
    ];

    protected static function booted(): void
    {
        static::addGlobalScope('tenant', function (Builder $builder): void {
            if (TenantContext::isSet()) {
                $builder->where('rc_applications.org_id', TenantContext::getOrganizationId());
            }
        });

        static::creating(function (self $model): void {
            if (empty($model->uuid)) {
                $model->uuid = (string) Str::uuid();
            }
            if (empty($model->org_id) && TenantContext::isSet()) {
                $model->org_id = TenantContext::getOrganizationId();
            }
            if (empty($model->applied_at)) {
                $model->applied_at = now();
            }
        });
    }

    public function scopeWithoutTenant(Builder $query): Builder
    {
        return $query->withoutGlobalScope('tenant');
    }

    // ── Relationships ────────────────────────────────────────────────

    public function candidate(): BelongsTo
    {
        return $this->belongsTo(RcCandidate::class, 'candidate_id');
    }

    public function currentStage(): BelongsTo
    {
        return $this->belongsTo(RcPipelineStage::class, 'current_stage_id');
    }

    public function assignedTo(): BelongsTo
    {
        return $this->belongsTo(User::class, 'assigned_to');
    }

    public function stageLogs(): HasMany
    {
        return $this->hasMany(RcApplicationStageLog::class, 'application_id');
    }

    public function answers(): HasMany
    {
        return $this->hasMany(RcApplicationAnswer::class, 'application_id');
    }

    public function interviews(): HasMany
    {
        return $this->hasMany(RcInterview::class, 'application_id');
    }

    public function offers(): HasMany
    {
        return $this->hasMany(RcOffer::class, 'application_id');
    }

    public function activeOffer(): ?RcOffer
    {
        return $this->offers()
            ->whereNotIn('status', ['rejected', 'expired', 'revoked'])
            ->latest()
            ->first();
    }
}
