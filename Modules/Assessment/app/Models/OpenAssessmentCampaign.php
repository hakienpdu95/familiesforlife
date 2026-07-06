<?php

namespace Modules\Assessment\Models;

use App\Shared\Tenancy\Models\Organization;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Str;
use Modules\Assessment\Enums\CampaignStatus;

class OpenAssessmentCampaign extends Model
{
    protected $table = 'open_assessment_campaigns';

    protected $fillable = [
        'uuid',
        'organization_id',
        'title',
        'description',
        'target_job_title_id',
        'target_job_title_label',
        'target_department_label',
        'min_trust_level',
        'min_tdwcf_score',
        'status',
        'open_from',
        'open_until',
        'max_participants',
        'is_anonymous_to_org',
        'participants_count',
        'completed_count',
    ];

    protected function casts(): array
    {
        return [
            'status'             => CampaignStatus::class,
            'open_from'          => 'datetime',
            'open_until'         => 'datetime',
            'min_trust_level'    => 'integer',
            'min_tdwcf_score'    => 'float',
            'is_anonymous_to_org'=> 'boolean',
            'participants_count' => 'integer',
            'completed_count'    => 'integer',
        ];
    }

    protected static function booted(): void
    {
        static::creating(function (self $c) {
            $c->uuid ??= (string) Str::uuid();
        });
    }

    public function getRouteKeyName(): string
    {
        return 'uuid';
    }

    // ── Relationships ────────────────────────────────────────────────

    public function organization(): BelongsTo
    {
        return $this->belongsTo(Organization::class);
    }

    public function domainRequirements(): HasMany
    {
        return $this->hasMany(CampaignDomainRequirement::class, 'campaign_id')->orderBy('domain_code');
    }

    public function sandboxTasks(): HasMany
    {
        return $this->hasMany(CampaignSandboxTask::class, 'campaign_id')->orderBy('sort_order');
    }

    public function participations(): HasMany
    {
        return $this->hasMany(CampaignParticipation::class, 'campaign_id');
    }

    // ── Helpers ──────────────────────────────────────────────────────

    public function isOpen(): bool
    {
        return $this->status === CampaignStatus::Open
            && ($this->open_from === null || $this->open_from->isPast())
            && ($this->open_until === null || $this->open_until->isFuture());
    }

    public function isFull(): bool
    {
        return $this->max_participants !== null
            && $this->participants_count >= $this->max_participants;
    }


}
