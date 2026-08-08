<?php

namespace Modules\Post\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Str;

/**
 * GEO đợt 6 (2026-08-08) — khối "So sánh" trong content-block composer, phục vụ "Comparison
 * fan-out" (spec/giadinh.md). Cùng quy ước PostHowtoBlock/PostFaqBlock: không soft-delete, không
 * tenant-scoped, không override/fallback (không tham chiếu entity ngoài).
 */
class PostComparisonBlock extends Model
{
    protected $table = 'post_comparison_blocks';

    protected $fillable = [
        'uuid',
        'translation_id',
        'name',
        'description',
        'sort_order',
    ];

    protected $casts = [
        'sort_order' => 'integer',
    ];

    protected static function booted(): void
    {
        static::creating(function (self $model): void {
            if (empty($model->uuid)) {
                $model->uuid = (string) Str::uuid();
            }
        });
    }

    public function translation(): BelongsTo
    {
        return $this->belongsTo(PostArticleTranslation::class, 'translation_id');
    }

    public function columns(): HasMany
    {
        return $this->hasMany(PostComparisonColumn::class, 'comparison_block_id')->orderBy('sort_order');
    }

    public function rows(): HasMany
    {
        return $this->hasMany(PostComparisonRow::class, 'comparison_block_id')->orderBy('sort_order');
    }
}
