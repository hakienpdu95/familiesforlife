<?php

namespace Modules\OcopRubric\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Str;

/**
 * 1 phiên bản bộ tiêu chí của 1 bộ sản phẩm — system-level, immutable sau khi publish
 * (xem PublishRubricVersionAction ở Phase 2). Không extend TenantAwareModel.
 */
class OcopRubricVersion extends Model
{
    protected $table = 'ocop_rubric_versions';

    protected $fillable = [
        'uuid', 'product_group_id', 'version_no', 'status',
        'effective_from', 'effective_to', 'source_reference',
        'total_max_score', 'published_by', 'published_at',
    ];

    protected $casts = [
        'effective_from'  => 'date',
        'effective_to'    => 'date',
        'published_at'    => 'datetime',
        'total_max_score' => 'decimal:2',
    ];

    protected static function booted(): void
    {
        static::creating(function (self $model): void {
            if (empty($model->uuid)) {
                $model->uuid = (string) Str::uuid();
            }
        });
    }

    public function productGroup(): BelongsTo
    {
        return $this->belongsTo(OcopProductGroup::class, 'product_group_id');
    }

    public function sections(): HasMany
    {
        return $this->hasMany(OcopRubricSection::class, 'rubric_version_id')->orderBy('sort_order');
    }

    public function disqualifiers(): HasMany
    {
        return $this->hasMany(OcopRubricDisqualifier::class, 'rubric_version_id')->orderBy('sort_order');
    }
}
