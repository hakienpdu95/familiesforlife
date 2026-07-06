<?php

namespace Modules\Assessment\Models;

use App\Foundation\Models\TenantAwareModel;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class WorkforcePortfolio extends TenantAwareModel
{
    protected $table = 'workforce_portfolios';

    protected $fillable = [
        'uuid',
        'organization_id',
        'workforce_profile_id',
        'item_type',
        'title',
        'description',
        'evidence_url',
        'kc_item_id',
        'approval_status',
        'approved_by',
        'approved_at',
        'rejection_reason',
        'sort_order',
    ];

    protected function casts(): array
    {
        return [
            'approved_at' => 'datetime',
        ];
    }

    public function profile(): BelongsTo
    {
        return $this->belongsTo(WorkforceProfile::class, 'workforce_profile_id');
    }
}
