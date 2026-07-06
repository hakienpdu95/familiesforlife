<?php

namespace Modules\KpiGoal\Models;

use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Modules\Employee\Models\Employee;

/**
 * Immutable snapshot — never UPDATE or DELETE after INSERT.
 * Does not use TenantAwareModel (no organization_id; access via employee relationship).
 */
class KpiSnapshot extends Model
{
    protected $table = 'kpi_snapshots';

    public $timestamps = false;

    protected $fillable = [
        'goal_id',
        'employee_id',
        'cycle_label',
        'target_value',
        'final_value',
        'achievement_pct',
        'weight_percent',
        'weighted_score',
        'kpi_total_score',
        'snapped_by',
        'snapped_at',
    ];

    protected $casts = [
        'target_value'   => 'decimal:4',
        'final_value'    => 'decimal:4',
        'achievement_pct'=> 'decimal:2',
        'weight_percent' => 'integer',
        'weighted_score' => 'decimal:2',
        'kpi_total_score'=> 'decimal:2',
        'snapped_at'     => 'datetime',
    ];

    /** Guard against accidental updates after creation */
    public function save(array $options = []): bool
    {
        if ($this->exists) {
            throw new \RuntimeException('KpiSnapshot is immutable — updates are not allowed.');
        }
        return parent::save($options);
    }

    public function delete(): bool|null
    {
        throw new \RuntimeException('KpiSnapshot is immutable — deletion is not allowed.');
    }

    // ── Relationships ─────────────────────────────────────────────────────────

    public function goal(): BelongsTo
    {
        return $this->belongsTo(KpiGoal::class, 'goal_id');
    }

    public function employee(): BelongsTo
    {
        return $this->belongsTo(Employee::class);
    }

    public function snappedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'snapped_by');
    }
}
