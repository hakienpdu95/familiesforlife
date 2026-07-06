<?php

namespace Modules\Deployment\Models;

use App\Models\User;
use App\Shared\Tenancy\Traits\BelongsToOrganization;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Modules\Employee\Models\Employee;

class DeploymentChecklistItem extends Model
{
    use BelongsToOrganization;

    public $timestamps = false;

    protected $fillable = [
        'organization_id',
        'deployment_target_id',
        'phase',
        'item_key',
        'item_label',
        'is_required',
        'is_done',
        'done_by',
        'done_at',
        'notes',
        'assigned_employee_id',
    ];

    protected $casts = [
        'is_required' => 'boolean',
        'is_done'     => 'boolean',
        'done_at'     => 'datetime',
    ];

    public function target(): BelongsTo
    {
        return $this->belongsTo(DeploymentTarget::class, 'deployment_target_id');
    }

    public function doneBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'done_by');
    }

    /** PM chỉ định trước ai phụ trách mục này — khác doneBy() (biết sau khi đã làm). */
    public function assignedEmployee(): BelongsTo
    {
        return $this->belongsTo(Employee::class, 'assigned_employee_id');
    }

    /** Nhật ký của riêng mục này — vừa log tự động khi tick, vừa ghi chú thủ công. */
    public function progressLogs(): HasMany
    {
        return $this->hasMany(DeploymentProgressLog::class, 'deployment_checklist_item_id')
            ->orderByDesc('logged_at');
    }

    /** % hoàn thành của cả phase chứa mục này — dùng chung cho toggle và ghi chú, tránh lệch số liệu. */
    public static function phaseCompletionPct(int $targetId, string $phase): int
    {
        $total = static::withoutTenant()
            ->where('deployment_target_id', $targetId)
            ->where('phase', $phase)
            ->count();

        $done = static::withoutTenant()
            ->where('deployment_target_id', $targetId)
            ->where('phase', $phase)
            ->where('is_done', true)
            ->count();

        return $total > 0 ? (int) round($done / $total * 100) : 0;
    }
}
