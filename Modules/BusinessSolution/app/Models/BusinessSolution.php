<?php

namespace Modules\BusinessSolution\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Str;

/**
 * Danh mục Business Solution — system-level (không extend TenantAwareModel).
 * organization_id chỉ đánh dấu solution riêng của 1 tổ chức nếu có (NULL = dùng chung toàn platform).
 */
class BusinessSolution extends Model
{
    use SoftDeletes;

    protected $table = 'business_solutions';

    protected $fillable = [
        'uuid', 'vertical_id', 'organization_id', 'code', 'name', 'slug',
        'short_description', 'description', 'target_customers',
        'status', 'visibility', 'thumbnail_url', 'metadata',
    ];

    protected $casts = [
        'target_customers' => 'array',
        'metadata'          => 'array',
    ];

    protected static function booted(): void
    {
        static::creating(function (self $model): void {
            if (empty($model->uuid)) {
                $model->uuid = (string) Str::uuid();
            }
            if (empty($model->slug)) {
                $model->slug = Str::slug($model->name);
            }
        });
    }

    public function vertical(): BelongsTo
    {
        return $this->belongsTo(Vertical::class);
    }

    public function versions(): HasMany
    {
        return $this->hasMany(BusinessSolutionVersion::class);
    }

    public function tags(): HasMany
    {
        return $this->hasMany(BusinessSolutionTag::class);
    }

    /** Blueprint kỹ thuật (Module BusinessBlueprint, Phần 2) thiết kế cho solution này. */
    public function blueprints(): HasMany
    {
        return $this->hasMany(\Modules\BusinessBlueprint\Models\Blueprint::class);
    }
}
