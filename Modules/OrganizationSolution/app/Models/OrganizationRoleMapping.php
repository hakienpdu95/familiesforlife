<?php

namespace Modules\OrganizationSolution\Models;

use App\Foundation\Models\TenantAwareModel;
use App\Models\User;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class OrganizationRoleMapping extends TenantAwareModel
{
    protected $table = 'organization_role_mappings';

    protected $fillable = [
        'organization_id', 'organization_solution_id', 'blueprint_role_code',
        'organization_role_id', 'user_id', 'mapping_type',
    ];

    public function organizationSolution(): BelongsTo
    {
        return $this->belongsTo(OrganizationSolution::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
