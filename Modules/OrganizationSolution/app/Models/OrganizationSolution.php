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

    /** Dùng bởi organization-solution:migrate-org-vertical-templates (diff checklist khi migrate từ VerticalTemplate). */
    public function checklistConfigs(): HasMany
    {
        return $this->hasMany(OrganizationChecklistConfig::class);
    }
}
