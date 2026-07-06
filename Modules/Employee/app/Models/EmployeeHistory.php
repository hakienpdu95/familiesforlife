<?php

namespace Modules\Employee\Models;

use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class EmployeeHistory extends Model
{
    public $timestamps = false;

    protected $fillable = [
        'organization_id',
        'employee_id',
        'changed_by',
        'change_type',
        'old_branch_id',
        'new_branch_id',
        'old_department_id',
        'new_department_id',
        'old_job_title_id',
        'new_job_title_id',
        'old_manager_id',
        'new_manager_id',
        'old_status',
        'new_status',
        'old_employment_type',
        'new_employment_type',
        'old_salary_base',
        'new_salary_base',
        'effective_date',
        'note',
    ];

    protected $casts = [
        'effective_date' => 'date',
    ];

    protected static function booted(): void
    {
        static::creating(function (self $model): void {
            $model->created_at = now();
        });
    }

    public function getTable(): string
    {
        return 'employee_history';
    }

    public function employee(): BelongsTo
    {
        return $this->belongsTo(Employee::class);
    }

    public function changedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'changed_by');
    }
}
