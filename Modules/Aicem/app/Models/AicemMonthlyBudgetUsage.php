<?php

namespace Modules\Aicem\Models;

use App\Foundation\Models\TenantAwareModel;

/** 1 dòng / (org, tháng) — khoá lockForUpdate để check-and-reserve chi phí O(1) (mục 13.1, Phase 4). */
class AicemMonthlyBudgetUsage extends TenantAwareModel
{
    protected $table = 'aicem_monthly_budget_usage';

    protected $fillable = [
        'year_month',
        'reserved_usd',
        'settled_usd',
    ];

    protected function casts(): array
    {
        return [
            'reserved_usd' => 'float',
            'settled_usd'  => 'float',
        ];
    }
}
