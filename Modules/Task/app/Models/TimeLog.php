<?php

namespace Modules\Task\Models;

use App\Foundation\Models\TenantAwareModel;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Str;
use Modules\Employee\Models\Employee;
use Modules\Project\Models\Project;

class TimeLog extends TenantAwareModel
{
    use SoftDeletes;

    protected $fillable = [
        'uuid',
        'organization_id',
        'task_id',
        'project_id',
        'employee_id',
        'hours',
        'log_date',
        'description',
        'is_billable',
    ];

    protected $casts = [
        'hours'       => 'decimal:2',
        'log_date'    => 'date',
        'is_billable' => 'boolean',
    ];

    protected static function boot(): void
    {
        parent::boot();

        static::creating(function (TimeLog $log) {
            if (empty($log->uuid)) {
                $log->uuid = (string) Str::uuid();
            }
        });
    }

    public function task(): BelongsTo
    {
        return $this->belongsTo(Task::class);
    }

    public function employee(): BelongsTo
    {
        return $this->belongsTo(Employee::class);
    }

    public function project(): BelongsTo
    {
        return $this->belongsTo(Project::class);
    }
}
