<?php

namespace Modules\Deployment\Models;

use App\Models\User;
use App\Shared\Tenancy\Models\Organization;
use App\Shared\Tenancy\Traits\BelongsToOrganization;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Modules\Employee\Models\Employee;
use Modules\Project\Models\Project;
use Modules\Survey\Models\SurveyResponse;
use Spatie\Activitylog\Support\LogOptions;
use Spatie\Activitylog\Models\Concerns\LogsActivity;

class DeploymentTarget extends Model
{
    use HasFactory;
    use BelongsToOrganization;
    use LogsActivity;

    protected static function bootDeploymentTarget(): void
    {
        static::creating(function (self $model): void {
            $model->uuid ??= (string) \Illuminate\Support\Str::uuid();
        });
    }

    protected $fillable = [
        'uuid',
        'organization_id',
        'project_id',
        'vertical_code',
        'deployment_id',
        'blueprint_version_id',
        'target_organization_id',
        'current_phase',
        'assigned_employee_id',
        'notes',
        'readiness_response_id',
        'readiness_score',
        'data_collection_response_id',
        'created_by',
    ];

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()->logFillable()->logOnlyDirty()->dontLogEmptyChanges();
    }

    // ── Relationships ────────────────────────────────────────────────────────

    public function project(): BelongsTo
    {
        return $this->belongsTo(Project::class);
    }

    public function deployment(): BelongsTo
    {
        return $this->belongsTo(\Modules\Deployment\Models\Deployment::class);
    }

    public function blueprintVersion(): BelongsTo
    {
        return $this->belongsTo(\Modules\BusinessBlueprint\Models\BlueprintVersion::class);
    }

    public function targetOrganization(): BelongsTo
    {
        return $this->belongsTo(Organization::class, 'target_organization_id');
    }

    public function assignedEmployee(): BelongsTo
    {
        return $this->belongsTo(Employee::class, 'assigned_employee_id');
    }

    public function createdBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function readinessResponse(): BelongsTo
    {
        return $this->belongsTo(SurveyResponse::class, 'readiness_response_id');
    }

    public function dataCollectionResponse(): BelongsTo
    {
        return $this->belongsTo(SurveyResponse::class, 'data_collection_response_id');
    }

    public function checklistItems(): HasMany
    {
        return $this->hasMany(DeploymentChecklistItem::class);
    }

    public function issues(): HasMany
    {
        return $this->hasMany(DeploymentIssue::class);
    }

    public function progressLogs(): HasMany
    {
        return $this->hasMany(DeploymentProgressLog::class)->orderByDesc('logged_at');
    }

    // ── Helpers ─────────────────────────────────────────────────────────────

    public function checklistForPhase(string $phase): HasMany
    {
        return $this->hasMany(DeploymentChecklistItem::class)->where('phase', $phase);
    }

    public function phaseProgress(string $phase): array
    {
        $items    = $this->checklistItems()->where('phase', $phase)->get();
        $total    = $items->count();
        $done     = $items->where('is_done', true)->count();
        $required = $items->where('is_required', true);

        return [
            'total'             => $total,
            'done'              => $done,
            'pct'               => $total > 0 ? round($done / $total * 100) : 0,
            'required_pending'  => $required->where('is_done', false)->count(),
        ];
    }
}
