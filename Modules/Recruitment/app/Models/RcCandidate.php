<?php

namespace Modules\Recruitment\Models;

use App\Models\User;
use App\Shared\Tenancy\TenantContext;
use App\Traits\HasTenantMedia;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Str;
use Modules\Recruitment\Enums\CandidateSource;
use Modules\Recruitment\Enums\CandidateStatus;
use Spatie\MediaLibrary\HasMedia;

class RcCandidate extends Model implements HasMedia
{
    use HasTenantMedia;

    protected $table = 'rc_candidates';

    protected $fillable = [
        'uuid',
        'org_id',
        'full_name',
        'email',
        'phone',
        'date_of_birth',
        'gender',
        'current_title',
        'current_company',
        'years_experience',
        'skills',
        'source',
        'referred_by',
        'mkt_applicant_id',
        'linkedin_url',
        'portfolio_url',
        'resume_url',
        'status',
        'blacklist_reason',
        'created_by',
        'updated_by',
    ];

    protected $casts = [
        'status'         => CandidateStatus::class,
        'source'         => CandidateSource::class,
        'date_of_birth'  => 'date',
    ];

    protected static function booted(): void
    {
        static::addGlobalScope('tenant', function (Builder $builder): void {
            if (TenantContext::isSet()) {
                $builder->where('rc_candidates.org_id', TenantContext::getOrganizationId());
            }
        });

        static::creating(function (self $model): void {
            if (empty($model->uuid)) {
                $model->uuid = (string) Str::uuid();
            }
            if (empty($model->org_id) && TenantContext::isSet()) {
                $model->org_id = TenantContext::getOrganizationId();
            }
        });
    }

    public function scopeWithoutTenant(Builder $query): Builder
    {
        return $query->withoutGlobalScope('tenant');
    }

    // ── Relationships ────────────────────────────────────────────────

    public function applications(): HasMany
    {
        return $this->hasMany(RcApplication::class, 'candidate_id');
    }

    public function createdBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function referredBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'referred_by');
    }

    public function notes(): HasMany
    {
        return $this->hasMany(RcCandidateNote::class, 'candidate_id');
    }

    public function attachments(): HasMany
    {
        return $this->hasMany(RcCandidateAttachment::class, 'candidate_id');
    }
}
