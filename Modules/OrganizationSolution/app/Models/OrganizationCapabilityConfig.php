<?php

namespace Modules\OrganizationSolution\Models;

use App\Foundation\Models\TenantAwareModel;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Modules\BusinessBlueprint\Models\BlueprintCapability;

class OrganizationCapabilityConfig extends TenantAwareModel
{
    protected $table = 'organization_capability_configs';

    protected $fillable = [
        'organization_id', 'organization_solution_id', 'blueprint_capability_id',
        'enabled', 'override_name',
    ];

    protected $casts = [
        'enabled' => 'boolean',
    ];

    public function organizationSolution(): BelongsTo
    {
        return $this->belongsTo(OrganizationSolution::class);
    }

    public function blueprintCapability(): BelongsTo
    {
        return $this->belongsTo(BlueprintCapability::class);
    }
}
