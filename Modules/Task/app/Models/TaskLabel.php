<?php

namespace Modules\Task\Models;

use App\Foundation\Models\TenantAwareModel;
use App\Models\User;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Str;
use Modules\Project\Models\Project;

class TaskLabel extends TenantAwareModel
{
    use SoftDeletes;

    public $timestamps = false;

    protected $fillable = [
        'uuid',
        'organization_id',
        'project_id',
        'name',
        'color_hex',
        'description',
        'created_by',
    ];

    protected static function boot(): void
    {
        parent::boot();

        static::creating(function (TaskLabel $label) {
            if (empty($label->uuid)) {
                $label->uuid = (string) Str::uuid();
            }
        });
    }

    public function project(): BelongsTo
    {
        return $this->belongsTo(Project::class);
    }

    public function tasks(): BelongsToMany
    {
        return $this->belongsToMany(Task::class, 'task_label_maps', 'label_id', 'task_id');
    }

    public function createdBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }
}
