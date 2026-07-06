<?php

namespace Modules\JobPosting\Models;

use App\Foundation\Models\TenantAwareModel;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Modules\JobPosting\Enums\BenefitCategory;

class JpBenefitMaster extends TenantAwareModel
{
    protected $table = 'jp_benefit_masters';

    protected $fillable = [
        'uuid',
        'organization_id',
        'name',
        'icon',
        'category',
        'is_active',
    ];

    protected $casts = [
        'category'  => BenefitCategory::class,
        'is_active' => 'boolean',
    ];

    public function jobPostBenefits(): HasMany
    {
        return $this->hasMany(JpJobPostBenefit::class, 'benefit_id');
    }
}
