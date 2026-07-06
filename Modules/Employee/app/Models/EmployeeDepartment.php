<?php

namespace Modules\Employee\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class EmployeeDepartment extends Model
{
    public $timestamps = false;

    protected $fillable = [
        'employee_id',
        'department_id',
        'is_primary',
        'role_in_dept',
        'joined_at',
        'left_at',
        'note',
    ];

    protected $casts = [
        'is_primary' => 'boolean',
        'joined_at'  => 'date',
        'left_at'    => 'date',
    ];

    protected static function booted(): void
    {
        static::creating(function (self $model): void {
            $model->created_at = now();
        });
    }

    public function getTable(): string
    {
        return 'employee_departments';
    }

    public function employee(): BelongsTo
    {
        return $this->belongsTo(Employee::class);
    }

    public function department(): BelongsTo
    {
        return $this->belongsTo(\Modules\Department\Models\Department::class);
    }
}
