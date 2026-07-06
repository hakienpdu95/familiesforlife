<?php

namespace Modules\OcopRubric\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * Bảng tra 5 hạng sao (Điều 3.3) — dùng chung cho MỌI bộ sản phẩm, không lặp lại
 * theo từng rubric_version. System-level, không extend TenantAwareModel.
 */
class OcopStarBand extends Model
{
    protected $table = 'ocop_star_bands';

    protected $fillable = [
        'legal_version', 'star_rank', 'label', 'min_score', 'max_score',
        'authority_level', 'is_certifiable',
    ];

    protected $casts = [
        'star_rank'      => 'integer',
        'min_score'      => 'decimal:2',
        'max_score'      => 'decimal:2',
        'is_certifiable' => 'boolean',
    ];
}
