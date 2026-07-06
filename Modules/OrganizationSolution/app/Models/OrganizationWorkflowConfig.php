<?php

namespace Modules\OrganizationSolution\Models;

use App\Foundation\Models\TenantAwareModel;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Modules\BusinessBlueprint\Models\BlueprintWorkflow;

class OrganizationWorkflowConfig extends TenantAwareModel
{
    protected $table = 'organization_workflow_configs';

    protected $fillable = [
        'organization_id', 'organization_solution_id', 'blueprint_workflow_id',
        'enabled', 'default_owner_id', 'sla_days',
    ];

    protected $casts = [
        'enabled' => 'boolean',
    ];

    public function organizationSolution(): BelongsTo
    {
        return $this->belongsTo(OrganizationSolution::class);
    }

    public function blueprintWorkflow(): BelongsTo
    {
        return $this->belongsTo(BlueprintWorkflow::class);
    }
}
