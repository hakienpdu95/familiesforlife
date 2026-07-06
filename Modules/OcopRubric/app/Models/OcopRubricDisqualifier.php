<?php

namespace Modules\OcopRubric\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Điều kiện "hồ sơ bị loại khi..." — chỉ mang tính khuyến cáo (advisory), không tự động
 * chặn hoàn thành phiên chấm. System-level, không extend TenantAwareModel.
 */
class OcopRubricDisqualifier extends Model
{
    protected $table = 'ocop_rubric_disqualifiers';

    protected $fillable = [
        'rubric_version_id', 'label', 'sort_order',
    ];

    public function rubricVersion(): BelongsTo
    {
        return $this->belongsTo(OcopRubricVersion::class, 'rubric_version_id');
    }
}
