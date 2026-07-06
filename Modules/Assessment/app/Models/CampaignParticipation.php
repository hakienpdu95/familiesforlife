<?php

namespace Modules\Assessment\Models;

use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Str;
use Modules\Assessment\Enums\ParticipationStatus;

class CampaignParticipation extends Model
{
    protected $table = 'campaign_participations';

    protected $fillable = [
        'uuid',
        'campaign_id',
        'user_id',
        'joined_at',
        'completed_at',
        'status',
        'result_tdwcf_score',
        'result_maturity_level',
        'result_sandbox_avg',
        'passport_entry_id',
        'org_rating',
        'org_note',
        'org_action',
        'org_action_at',
    ];

    protected function casts(): array
    {
        return [
            'status'              => ParticipationStatus::class,
            'joined_at'           => 'datetime',
            'completed_at'        => 'datetime',
            'org_action_at'       => 'datetime',
            'result_tdwcf_score'  => 'float',
            'result_sandbox_avg'  => 'float',
        ];
    }

    protected static function booted(): void
    {
        static::creating(function (self $p) {
            $p->uuid ??= (string) Str::uuid();
        });
    }

    public function getRouteKeyName(): string
    {
        return 'uuid';
    }

    // ── Relationships ────────────────────────────────────────────────

    public function campaign(): BelongsTo
    {
        return $this->belongsTo(OpenAssessmentCampaign::class, 'campaign_id');
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function passportEntry(): BelongsTo
    {
        return $this->belongsTo(PassportEntry::class, 'passport_entry_id');
    }

    public function scores(): HasMany
    {
        return $this->hasMany(CampaignParticipationScore::class, 'participation_id');
    }

    // ── Helpers ──────────────────────────────────────────────────────

    public function isCompleted(): bool
    {
        return $this->status === ParticipationStatus::Completed;
    }

    public function isInvited(): bool
    {
        return $this->org_action === 'invited';
    }

    /** Anonymous display label for org view */
    public function anonymousLabel(): string
    {
        return 'Ứng viên #' . $this->id;
    }
}
