<?php

namespace Modules\PensionCalculator\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * spec §6.9/§8.1/§14 mục 1 — bảng tỷ lệ hưởng lương hưu hằng tháng theo (gender, số năm đóng).
 * CHƯA xác minh với Luật Bảo hiểm xã hội 2024 tại thời điểm v1.1 — bảng CÓ THỂ rỗng, mọi nơi
 * đọc bảng này phải xử lý trường hợp rỗng bằng cảnh báo "chưa xác minh", KHÔNG suy đoán số liệu.
 */
class PensionRateBracket extends Model
{
    protected $fillable = [
        'gender',
        'min_years_for_base_rate',
        'base_rate_percent',
        'increment_percent_per_year',
        'max_rate_percent',
        'effective_from',
        'source_document',
        'notes',
    ];

    protected $casts = [
        'min_years_for_base_rate' => 'integer',
        'base_rate_percent' => 'decimal:2',
        'increment_percent_per_year' => 'decimal:2',
        'max_rate_percent' => 'decimal:2',
        'effective_from' => 'date',
    ];
}
