<?php

namespace Modules\Deployment\Models;

use App\Shared\Tenancy\Traits\BelongsToOrganization;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Modules\BusinessBlueprint\Models\Blueprint;
use Modules\BusinessBlueprint\Models\BlueprintVersion;
use Modules\BusinessSolution\Models\BusinessSolution;
use Modules\OrganizationSolution\Models\OrganizationSolution;
use Modules\Project\Models\Project;
use Spatie\Activitylog\Models\Concerns\LogsActivity;
use Spatie\Activitylog\Support\LogOptions;

/**
 * Ghi nhận 1 lần chạy DeployOrganizationSolutionAction — tương tự DeploymentTarget,
 * dùng BelongsToOrganization trực tiếp (không phải TenantAwareModel đầy đủ) vì đây là
 * bản ghi tiến trình có state transition đáng audit nhưng không cần soft-delete
 * (đúng convention hiện có của module này, xem DeploymentTarget).
 */
class Deployment extends Model
{
    use HasFactory;
    use BelongsToOrganization;
    use LogsActivity;

    protected $fillable = [
        'organization_id', 'organization_solution_id', 'business_solution_id',
        'blueprint_id', 'blueprint_version_id', 'project_id', 'deployed_by',
        'status', 'started_at', 'completed_at',
    ];

    protected $casts = [
        'started_at'   => 'datetime',
        'completed_at' => 'datetime',
    ];

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()->logFillable()->logOnlyDirty()->dontLogEmptyChanges();
    }

    public function organizationSolution(): BelongsTo
    {
        return $this->belongsTo(OrganizationSolution::class);
    }

    public function businessSolution(): BelongsTo
    {
        return $this->belongsTo(BusinessSolution::class);
    }

    public function blueprint(): BelongsTo
    {
        return $this->belongsTo(Blueprint::class);
    }

    public function blueprintVersion(): BelongsTo
    {
        return $this->belongsTo(BlueprintVersion::class);
    }

    public function project(): BelongsTo
    {
        return $this->belongsTo(Project::class);
    }

    public function logs(): HasMany
    {
        return $this->hasMany(DeploymentLog::class)->orderBy('created_at');
    }

    public function snapshots(): HasMany
    {
        return $this->hasMany(DeploymentSnapshot::class);
    }
}
