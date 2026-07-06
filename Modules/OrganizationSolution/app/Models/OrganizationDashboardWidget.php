<?php

namespace Modules\OrganizationSolution\Models;

use App\Foundation\Models\TenantAwareModel;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Modules\BusinessBlueprint\Models\BlueprintAnalytic;

class OrganizationDashboardWidget extends TenantAwareModel
{
    protected $table = 'organization_dashboard_widgets';

    protected $fillable = [
        'organization_id', 'organization_solution_id', 'blueprint_analytic_id',
        'widget_type', 'title', 'enabled', 'sort_order',
    ];

    protected $casts = [
        'enabled' => 'boolean',
    ];

    public function organizationSolution(): BelongsTo
    {
        return $this->belongsTo(OrganizationSolution::class);
    }

    public function blueprintAnalytic(): BelongsTo
    {
        return $this->belongsTo(BlueprintAnalytic::class);
    }
}
