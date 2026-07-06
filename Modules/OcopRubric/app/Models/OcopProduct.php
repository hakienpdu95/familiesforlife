<?php

namespace Modules\OcopRubric\Models;

use App\Foundation\Models\TenantAwareModel;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Str;

/**
 * 1 sản phẩm OCOP thật của 1 tổ chức — tenant-scoped (TenantAwareModel).
 * `best_practice_*` (kỷ lục luyện tập) và `latest_self_assessment_*` (hiện
 * trạng tự đánh giá mới nhất) là 2 cặp cột độc lập, cố ý KHÔNG dùng chung —
 * xem spec §18 Key Design Decisions.
 */
class OcopProduct extends TenantAwareModel
{
    protected $table = 'ocop_products';

    protected $fillable = [
        'uuid', 'organization_id', 'product_group_id', 'name', 'product_code', 'status',
        'best_practice_score', 'best_practice_star_rank',
        'latest_self_assessment_score', 'latest_self_assessment_star_rank',
        'latest_self_assessment_session_id', 'created_by',
    ];

    protected $casts = [
        'best_practice_score'          => 'decimal:2',
        'latest_self_assessment_score' => 'decimal:2',
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

    /**
     * Luôn resolve "động" theo thời điểm gọi — nếu rubric vừa được publish version
     * mới thì trả về ngay, không cache. Không lưu rubric_version_id cố định trên
     * OcopProduct vì 1 sản phẩm có thể có nhiều session ở nhiều version khác nhau
     * theo thời gian (rubric_version_id chỉ tồn tại ở cấp OcopScoringSession).
     */
    public function activeRubricVersion(): ?OcopRubricVersion
    {
        return OcopRubricVersion::where('product_group_id', $this->product_group_id)
            ->where('status', 'active')
            ->first();
    }
}
