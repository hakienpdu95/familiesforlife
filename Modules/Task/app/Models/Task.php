<?php

namespace Modules\Task\Models;

use App\Foundation\Models\TenantAwareModel;
use App\Models\User;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Str;
use Modules\Employee\Models\Employee;
use Modules\Project\Models\Project;
use Modules\Task\Enums\TaskPriority;
use Modules\Task\Enums\TaskStatus;
use Modules\Task\Enums\TaskType;

class Task extends TenantAwareModel
{
    use SoftDeletes;

    protected $fillable = [
        'uuid',
        'organization_id',
        'project_id',
        'parent_id',
        'employee_id',
        'title',
        'description',
        'task_type',
        'status',
        'priority',
        'story_points',
        'start_date',
        'due_date',
        'completed_at',
        'estimated_hours',
        'logged_hours',
        'progress_pct',
        'is_leaf',
        'subtask_total',
        'subtask_done',
        'comment_count',
        'attachment_count',
        'sort_order',
        'depth',
        'is_archived',
        'created_by',
        'updated_by',
    ];

    protected $casts = [
        'task_type'        => TaskType::class,
        'status'           => TaskStatus::class,
        'priority'         => TaskPriority::class,
        'start_date'       => 'date',
        'due_date'         => 'date',
        'completed_at'     => 'datetime',
        'estimated_hours'  => 'decimal:2',
        'logged_hours'     => 'decimal:2',
        'progress_pct'     => 'integer',
        'story_points'     => 'integer',
        'is_leaf'          => 'boolean',
        'is_archived'      => 'boolean',
    ];

    protected static function boot(): void
    {
        parent::boot();

        static::creating(function (Task $task) {
            if (empty($task->uuid)) {
                $task->uuid = (string) Str::uuid();
            }
        });
    }

    // ── Relationships ────────────────────────────────────────────────────────

    public function project(): BelongsTo
    {
        return $this->belongsTo(Project::class);
    }

    public function parent(): BelongsTo
    {
        return $this->belongsTo(Task::class, 'parent_id');
    }

    public function children(): HasMany
    {
        return $this->hasMany(Task::class, 'parent_id');
    }

    public function employee(): BelongsTo
    {
        return $this->belongsTo(Employee::class);
    }

    public function labels(): BelongsToMany
    {
        return $this->belongsToMany(TaskLabel::class, 'task_label_maps', 'task_id', 'label_id')
            ->withPivot('created_at');
    }

    public function createdBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function updatedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'updated_by');
    }

    public function comments(): HasMany
    {
        return $this->hasMany(TaskComment::class)->whereNull('parent_id')->orderBy('created_at');
    }

    public function watchers(): HasMany
    {
        return $this->hasMany(TaskWatcher::class);
    }

    public function timeLogs(): HasMany
    {
        return $this->hasMany(TimeLog::class);
    }

    public function histories(): HasMany
    {
        return $this->hasMany(TaskHistory::class)->orderByDesc('changed_at');
    }

    public function isWatchedBy(int $userId): bool
    {
        return $this->watchers()->where('user_id', $userId)->exists();
    }
}
