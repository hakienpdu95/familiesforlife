<?php

namespace Modules\OrgChart\Models;

use App\Foundation\Models\TenantAwareModel;
use App\Models\User;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Modules\Branch\Models\Branch;
use Modules\OrgChart\Enums\OrgChartGroupBy;
use Modules\OrgChart\Enums\OrgChartViewType;

class OrgChartConfig extends TenantAwareModel
{
    protected $table = 'org_chart_configs';

    protected $fillable = [
        'organization_id',
        'created_by',
        'name',
        'view_type',
        'group_by',
        'scope_branch_id',
        'show_avatar',
        'show_job_title',
        'show_employee_code',
        'show_department',
        'show_branch',
        'max_depth',
        'expand_by_default',
        'is_default',
    ];

    protected $casts = [
        'view_type'          => OrgChartViewType::class,
        'group_by'           => OrgChartGroupBy::class,
        'show_avatar'        => 'boolean',
        'show_job_title'     => 'boolean',
        'show_employee_code' => 'boolean',
        'show_department'    => 'boolean',
        'show_branch'        => 'boolean',
        'expand_by_default'  => 'boolean',
        'is_default'         => 'boolean',
        'max_depth'          => 'integer',
    ];

    public function scopeBranch(): BelongsTo
    {
        return $this->belongsTo(Branch::class, 'scope_branch_id');
    }

    public function createdBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }
}
