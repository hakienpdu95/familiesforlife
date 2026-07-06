<?php

namespace Modules\OcopRubric\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Str;

/**
 * 1 trong 26 "Bộ sản phẩm" của Phụ lục I — system-level, dùng chung mọi tổ chức,
 * không extend TenantAwareModel (không phải dữ liệu của riêng tổ chức nào).
 */
class OcopProductGroup extends Model
{
    use SoftDeletes;

    protected $table = 'ocop_product_groups';

    protected $fillable = [
        'uuid', 'code', 'name', 'industry_code', 'industry_name',
        'group_label', 'managing_agency', 'requires_sample_product',
        'is_active', 'sort_order',
    ];

    protected $casts = [
        'requires_sample_product' => 'boolean',
        'is_active'               => 'boolean',
        'sort_order'              => 'integer',
    ];

    protected static function booted(): void
    {
        static::creating(function (self $model): void {
            if (empty($model->uuid)) {
                $model->uuid = (string) Str::uuid();
            }
        });
    }

    public function rubricVersions(): HasMany
    {
        return $this->hasMany(OcopRubricVersion::class, 'product_group_id');
    }

    /** Chỉ 1 version active tại 1 thời điểm — dùng cho link "Xem cây" đi thẳng, không qua trang danh sách. */
    public function activeRubricVersion(): HasOne
    {
        return $this->hasOne(OcopRubricVersion::class, 'product_group_id')->where('status', 'active');
    }
}
