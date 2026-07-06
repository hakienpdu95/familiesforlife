<?php

namespace Modules\OrganizationSolution\Models;

use App\Foundation\Models\TenantAwareModel;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Modules\BusinessBlueprint\Models\BlueprintResourceLink;

class OrganizationResourceOverride extends TenantAwareModel
{
    protected $table = 'organization_resource_overrides';

    protected $fillable = [
        'organization_id', 'organization_solution_id', 'blueprint_resource_link_id',
        'override_reference',
    ];

    public function organizationSolution(): BelongsTo
    {
        return $this->belongsTo(OrganizationSolution::class);
    }

    public function blueprintResourceLink(): BelongsTo
    {
        return $this->belongsTo(BlueprintResourceLink::class);
    }
}
