<?php

namespace Modules\Survey\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class SurveyToken extends Model
{
    protected $fillable = [
        'survey_id',
        'name',
        'token_encrypted',
        'is_active',
        'last_used_at',
        'expires_at',
        'usage_limit',
        'usage_count',
    ];

    // Không serialize token_encrypted ra ngoài API response
    protected $hidden = ['token', 'token_encrypted'];

    protected $casts = [
        'is_active'    => 'boolean',
        'last_used_at' => 'datetime',
        'expires_at'   => 'datetime',
        'usage_limit'  => 'integer',
        'usage_count'  => 'integer',
    ];

    // ── Relations ─────────────────────────────────────────────────────

    public function survey(): BelongsTo
    {
        return $this->belongsTo(Survey::class);
    }

    // ── Scopes ────────────────────────────────────────────────────────

    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    public function scopeForSurvey($query, int $surveyId)
    {
        return $query->where('survey_id', $surveyId);
    }

    // ── Helpers ───────────────────────────────────────────────────────

    public function isExpired(): bool
    {
        return $this->expires_at !== null && $this->expires_at->isPast();
    }

    public function isValid(): bool
    {
        return $this->is_active && !$this->isExpired();
    }

    public function hasReachedUsageLimit(): bool
    {
        return $this->usage_limit !== null && $this->usage_count >= $this->usage_limit;
    }
}
