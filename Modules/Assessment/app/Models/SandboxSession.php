<?php

namespace Modules\Assessment\Models;

use App\Foundation\Models\TenantAwareModel;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

class SandboxSession extends TenantAwareModel
{
    protected $table = 'sandbox_sessions';

    /**
     * Bypass tenant scope during route model binding so the controller
     * can handle authorization explicitly (user_id check + policy).
     */
    public function resolveRouteBinding($value, $field = null): ?static
    {
        return $this->withoutTenant()
            ->where($field ?? $this->getRouteKeyName(), $value)
            ->first();
    }

    protected $fillable = [
        'uuid',
        'organization_id',
        'sandbox_task_id',
        'workforce_profile_id',
        'user_id',
        'status',
        'started_at',
        'submitted_at',
        'completed_at',
        'duration_minutes',
        'quality_score',
        'productivity_score',
        'ai_adoption_score',
        'final_score',
        'passed',
        'evaluator_user_id',
        'evaluated_at',
        'feedback',
    ];

    protected function casts(): array
    {
        return [
            'started_at'       => 'datetime',
            'submitted_at'     => 'datetime',
            'completed_at'     => 'datetime',
            'evaluated_at'     => 'datetime',
            'quality_score'    => 'float',
            'productivity_score' => 'float',
            'ai_adoption_score'=> 'float',
            'final_score'      => 'float',
            'passed'           => 'boolean',
        ];
    }

    // Final Score = Quality×40% + Productivity×35% + AI Adoption×25%
    public function calculateFinalScore(): float
    {
        return round(
            ($this->quality_score     ?? 0) * 0.40 +
            ($this->productivity_score ?? 0) * 0.35 +
            ($this->ai_adoption_score  ?? 0) * 0.25,
        2);
    }

    public function task(): BelongsTo
    {
        return $this->belongsTo(SandboxTask::class, 'sandbox_task_id');
    }

    public function profile(): BelongsTo
    {
        return $this->belongsTo(WorkforceProfile::class, 'workforce_profile_id');
    }

    public function submission(): HasOne
    {
        return $this->hasOne(SandboxSubmission::class, 'sandbox_session_id');
    }

    public function activities(): HasMany
    {
        return $this->hasMany(SandboxActivity::class, 'sandbox_session_id')->orderBy('occurred_at');
    }
}
