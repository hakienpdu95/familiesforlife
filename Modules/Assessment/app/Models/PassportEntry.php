<?php

namespace Modules\Assessment\Models;

use App\Models\User;
use App\Shared\Tenancy\Models\Organization;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Str;

class PassportEntry extends Model
{
    protected $table = 'passport_entries';

    protected $fillable = [
        'uuid',
        'user_id',
        'entry_type',
        'source_org_id',
        'source_org_name',
        'source_org_logo_path',
        'snapshot_at',
        'tenure_start',
        'tenure_end',
        'tenure_months',
        'job_title_at_exit',
        'department_at_exit',
        'role_at_exit',
        'tdwcf_score',
        'tdwcf_maturity_level',
        'workforce_trust_score',
        'ai_readiness_score',
        'sandbox_hours_total',
        'sandbox_score_avg',
        'certifications_count',
        'highest_cert_level',
        'impact_entries_count',
        'visibility',
        'share_token',
        'share_token_expires_at',
        'org_verified',
        'org_verified_at',
        'org_verified_by_user_id',
        'offboarded_at',
        'has_late_offboard_gap',
        'personal_note',
    ];

    // Immutable after snapshot_at is set
    private array $immutableFields = [
        'tdwcf_score', 'tdwcf_maturity_level', 'workforce_trust_score',
        'ai_readiness_score', 'sandbox_hours_total', 'sandbox_score_avg',
        'certifications_count', 'highest_cert_level', 'impact_entries_count',
        'source_org_id', 'source_org_name', 'snapshot_at',
        'tenure_start', 'tenure_end', 'tenure_months',
        'job_title_at_exit', 'department_at_exit', 'role_at_exit',
        'entry_type', 'user_id',
    ];

    protected function casts(): array
    {
        return [
            'snapshot_at'             => 'datetime',
            'tenure_start'            => 'date',
            'tenure_end'              => 'date',
            'share_token_expires_at'  => 'datetime',
            'org_verified_at'         => 'datetime',
            'offboarded_at'           => 'datetime',
            'org_verified'            => 'boolean',
            'has_late_offboard_gap'   => 'boolean',
            'tdwcf_score'             => 'float',
            'workforce_trust_score'   => 'float',
            'ai_readiness_score'      => 'float',
            'sandbox_score_avg'       => 'float',
        ];
    }

    protected static function booted(): void
    {
        static::creating(function (PassportEntry $entry) {
            if (empty($entry->uuid)) {
                $entry->uuid = (string) Str::uuid();
            }
        });

        static::updating(function (PassportEntry $entry) {
            foreach ($entry->immutableFields as $field) {
                if ($entry->isDirty($field)) {
                    throw new \LogicException("Cannot modify immutable field '{$field}' on PassportEntry after creation.");
                }
            }
        });
    }

    // ── Relationships ────────────────────────────────────────────────

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function sourceOrg(): BelongsTo
    {
        return $this->belongsTo(Organization::class, 'source_org_id');
    }

    public function domainScores(): HasMany
    {
        return $this->hasMany(PassportDomainScore::class);
    }

    public function certifications(): HasMany
    {
        return $this->hasMany(PassportCertification::class)->orderBy('level_order', 'desc');
    }

    public function impactHighlights(): HasMany
    {
        return $this->hasMany(PassportImpactHighlight::class)->orderBy('sort_order');
    }

    public function sandboxSummaries(): HasMany
    {
        return $this->hasMany(PassportSandboxSummary::class);
    }

    // ── Helpers ──────────────────────────────────────────────────────

    public function isOrgTenure(): bool
    {
        return $this->entry_type === 'org_tenure';
    }

    public function isShareable(): bool
    {
        return $this->visibility !== 'private';
    }

    public function hasValidShareToken(): bool
    {
        return $this->share_token !== null
            && ($this->share_token_expires_at === null || $this->share_token_expires_at->isFuture());
    }

    public function getRouteKeyName(): string
    {
        return 'uuid';
    }
}
