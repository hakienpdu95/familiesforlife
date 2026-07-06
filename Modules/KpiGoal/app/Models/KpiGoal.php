<?php

namespace Modules\KpiGoal\Models;

use App\Foundation\Models\TenantAwareModel;
use App\Models\User;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Modules\Employee\Models\Employee;
use Modules\KpiGoal\Enums\KpiDirection;
use Modules\KpiGoal\Enums\KpiGoalStatus;
use Modules\KpiGoal\Enums\KpiGoalType;
use Modules\KpiGoal\Observers\KpiGoalObserver;

class KpiGoal extends TenantAwareModel
{
    protected $table = 'kpi_goals';

    protected $fillable = [
        'uuid',
        'organization_id',
        'employee_id',
        'cycle_label',
        'cycle_start',
        'cycle_end',
        'parent_goal_id',
        'title',
        'description',
        'goal_type',
        'target_value',
        'current_value',
        'unit',
        'direction',
        'achievement_pct',
        'weight_percent',
        'status',
        'last_synced_at',
        'approved_by',
        'approved_at',
        'created_by',
        'ai_impact_category',
        'ai_impact_type',
        'baseline_value',
        'investment_cost',
    ];

    protected $casts = [
        'goal_type'      => KpiGoalType::class,
        'direction'      => KpiDirection::class,
        'status'         => KpiGoalStatus::class,
        'target_value'   => 'decimal:4',
        'current_value'  => 'decimal:4',
        'achievement_pct'=> 'decimal:2',
        'weight_percent' => 'integer',
        'cycle_start'    => 'date',
        'cycle_end'      => 'date',
        'approved_at'    => 'datetime',
        'last_synced_at' => 'datetime',
    ];

    protected static function booted(): void
    {
        static::observe(KpiGoalObserver::class);
    }

    // ── Relationships ─────────────────────────────────────────────────────────

    public function employee(): BelongsTo
    {
        return $this->belongsTo(Employee::class);
    }

    public function parentGoal(): BelongsTo
    {
        return $this->belongsTo(KpiGoal::class, 'parent_goal_id');
    }

    public function childGoals(): HasMany
    {
        return $this->hasMany(KpiGoal::class, 'parent_goal_id');
    }

    public function approvedBy(): BelongsTo
    {
        return $this->belongsTo(Employee::class, 'approved_by');
    }

    public function createdBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function snapshot(): HasOne
    {
        return $this->hasOne(KpiSnapshot::class, 'goal_id');
    }

    // ── Scopes ───────────────────────────────────────────────────────────────

    public function scopeActive($query)
    {
        return $query->where('status', KpiGoalStatus::Active->value);
    }

    public function scopeForCycle($query, string $cycleLabel)
    {
        return $query->where('cycle_label', $cycleLabel);
    }

    public function scopeWeighted($query)
    {
        return $query->whereIn('status', KpiGoalStatus::weightedStatuses());
    }

    // ── Helpers ───────────────────────────────────────────────────────────────

    public function isDraft(): bool
    {
        return $this->status === KpiGoalStatus::Draft;
    }

    public function isActive(): bool
    {
        return $this->status === KpiGoalStatus::Active;
    }

    public function isEditable(): bool
    {
        return in_array($this->status, [KpiGoalStatus::Draft, KpiGoalStatus::Active]);
    }

    public function getWeightedContributionAttribute(): float
    {
        return round((float) $this->achievement_pct * $this->weight_percent / 100, 2);
    }
}
