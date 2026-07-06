<?php

namespace Modules\OrganizationSolution\Models;

use App\Foundation\Models\TenantAwareModel;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Modules\BusinessBlueprint\Models\BlueprintChecklist;

class OrganizationChecklistConfig extends TenantAwareModel
{
    protected $table = 'organization_checklist_configs';

    protected $fillable = [
        'organization_id', 'organization_solution_id', 'blueprint_checklist_id',
        'enabled', 'default_assignee_id', 'default_reviewer_id', 'due_days',
    ];

    protected $casts = [
        'enabled' => 'boolean',
    ];

    public function organizationSolution(): BelongsTo
    {
        return $this->belongsTo(OrganizationSolution::class);
    }

    public function blueprintChecklist(): BelongsTo
    {
        return $this->belongsTo(BlueprintChecklist::class);
    }
}
