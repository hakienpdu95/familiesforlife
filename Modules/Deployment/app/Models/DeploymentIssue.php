<?php

namespace Modules\Deployment\Models;

use App\Models\User;
use App\Shared\Tenancy\Traits\BelongsToOrganization;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Modules\Deployment\Enums\IssueSeverity;
use Modules\Deployment\Enums\IssueStatus;
use Modules\Project\Models\Project;
use Spatie\Activitylog\Support\LogOptions;
use Spatie\Activitylog\Models\Concerns\LogsActivity;

class DeploymentIssue extends Model
{
    use HasFactory;
    use BelongsToOrganization;
    use LogsActivity;

    protected $fillable = [
        'organization_id',
        'deployment_target_id',
        'project_id',
        'title',
        'description',
        'severity',
        'issue_type',
        'severity_detail',
        'status',
        'owner_id',
        'resolved_at',
        'created_by',
    ];

    protected $casts = [
        'severity'    => IssueSeverity::class,
        'status'      => IssueStatus::class,
        'resolved_at' => 'datetime',
    ];

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()->logFillable()->logOnlyDirty()->dontLogEmptyChanges();
    }

    // ── Relationships ────────────────────────────────────────────────────────

    public function target(): BelongsTo
    {
        return $this->belongsTo(DeploymentTarget::class, 'deployment_target_id');
    }

    public function project(): BelongsTo
    {
        return $this->belongsTo(Project::class);
    }

    public function owner(): BelongsTo
    {
        return $this->belongsTo(User::class, 'owner_id');
    }

    public function createdBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    // ── Helpers ─────────────────────────────────────────────────────────────

    public function isActive(): bool
    {
        return $this->status?->isActive() ?? false;
    }
}
