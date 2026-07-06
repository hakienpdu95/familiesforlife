<?php

namespace Modules\OrganizationSolution\Models;

use App\Foundation\Models\TenantAwareModel;
use App\Models\User;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Modules\BusinessBlueprint\Models\BlueprintVersion;
use Modules\BusinessSolution\Models\BusinessSolution;

class OrganizationSolution extends TenantAwareModel
{
    protected $table = 'organization_solutions';

    protected $fillable = [
        'organization_id', 'business_solution_id', 'blueprint_version_id',
        'name', 'owner_id', 'status', 'activated_at',
    ];

    protected $casts = [
        'activated_at' => 'datetime',
    ];

    public function businessSolution(): BelongsTo
    {
        return $this->belongsTo(BusinessSolution::class);
    }

    public function blueprintVersion(): BelongsTo
    {
        return $this->belongsTo(BlueprintVersion::class);
    }

    public function owner(): BelongsTo
    {
        return $this->belongsTo(User::class, 'owner_id');
    }

    public function capabilityConfigs(): HasMany
    {
        return $this->hasMany(OrganizationCapabilityConfig::class);
    }

    public function workflowConfigs(): HasMany
    {
        return $this->hasMany(OrganizationWorkflowConfig::class);
    }

    public function checklistConfigs(): HasMany
    {
        return $this->hasMany(OrganizationChecklistConfig::class);
    }

    public function roleMappings(): HasMany
    {
        return $this->hasMany(OrganizationRoleMapping::class);
    }

    public function aiConfigs(): HasMany
    {
        return $this->hasMany(OrganizationAiConfig::class);
    }

    public function resourceOverrides(): HasMany
    {
        return $this->hasMany(OrganizationResourceOverride::class);
    }

    public function dashboardWidgets(): HasMany
    {
        return $this->hasMany(OrganizationDashboardWidget::class)->orderBy('sort_order');
    }

    /** Deployment Engine (Module Deployment, Phần 4) — lịch sử các lần deploy. */
    public function deployments(): HasMany
    {
        return $this->hasMany(\Modules\Deployment\Models\Deployment::class)->orderByDesc('created_at');
    }
}
