<?php

namespace Modules\PerformanceReview\Models;

use App\Foundation\Models\TenantAwareModel;
use App\Models\User;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Modules\PerformanceReview\Enums\PeriodType;

class ReviewTemplate extends TenantAwareModel
{
    protected $fillable = [
        'uuid',
        'organization_id',
        'created_by',
        'name',
        'period_type',
        'apply_to_function',
        'rating_scale',
        'is_system',
        'is_locked',
        'is_active',
    ];

    protected $casts = [
        'period_type' => PeriodType::class,
        'rating_scale' => 'integer',
        'is_system' => 'boolean',
        'is_locked' => 'boolean',
        'is_active' => 'boolean',
    ];

    public function createdBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function criteria(): HasMany
    {
        return $this->hasMany(ReviewCriteria::class, 'template_id')->orderBy('sort_order');
    }

    public function reviews(): HasMany
    {
        return $this->hasMany(PerformanceReview::class, 'template_id');
    }
}
