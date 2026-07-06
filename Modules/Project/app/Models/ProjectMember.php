<?php

namespace Modules\Project\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Modules\Employee\Models\Employee;
use Modules\Project\Enums\ProjectMemberRole;

class ProjectMember extends Model
{
    public $timestamps = false;

    protected $fillable = [
        'project_id',
        'employee_id',
        'role',
        'is_lead',
        'contribution_pct',
        'joined_at',
        'left_at',
        'note',
        'created_at',
    ];

    protected $casts = [
        'role'             => ProjectMemberRole::class,
        'is_lead'          => 'boolean',
        'joined_at'        => 'date',
        'left_at'          => 'date',
        'contribution_pct' => 'integer',
        'created_at'       => 'datetime',
    ];

    protected static function boot(): void
    {
        parent::boot();
        static::creating(function ($model) {
            if (empty($model->created_at)) {
                $model->created_at = now();
            }
        });
    }

    public function project(): BelongsTo
    {
        return $this->belongsTo(Project::class);
    }

    public function employee(): BelongsTo
    {
        return $this->belongsTo(Employee::class);
    }
}
