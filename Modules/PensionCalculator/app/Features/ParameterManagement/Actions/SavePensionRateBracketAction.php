<?php

namespace Modules\PensionCalculator\Features\ParameterManagement\Actions;

use Lorisleiva\Actions\Concerns\AsAction;
use Modules\PensionCalculator\Models\PensionRateBracket;

/**
 * spec/bhxh/PensionCalculator_Technical_Specification.md §6.9/§8.1/§14 mục 1 — tạo 1 bậc tỷ lệ
 * hưởng lương hưu (gender, số năm đóng tối thiểu, tỷ lệ nền, tỷ lệ tăng thêm/năm, trần). Bảng
 * `pension_rate_brackets` để trống cho tới khi admin có văn bản Luật Bảo hiểm xã hội 2024 đã
 * xác minh (Điều 66/99) — action này chỉ là đường nhập liệu, KHÔNG tự seed số liệu suy đoán.
 */
class SavePensionRateBracketAction
{
    use AsAction;

    /**
     * @param  array{gender:string, min_years_for_base_rate:int, base_rate_percent:float,
     *     increment_percent_per_year:float, max_rate_percent:float, effective_from:string,
     *     source_document:string, notes:?string} $data
     */
    public function handle(array $data): PensionRateBracket
    {
        return PensionRateBracket::create([
            'gender' => $data['gender'],
            'min_years_for_base_rate' => $data['min_years_for_base_rate'],
            'base_rate_percent' => $data['base_rate_percent'],
            'increment_percent_per_year' => $data['increment_percent_per_year'],
            'max_rate_percent' => $data['max_rate_percent'] ?? 75.00,
            'effective_from' => $data['effective_from'],
            'source_document' => $data['source_document'],
            'notes' => $data['notes'] ?? null,
        ]);
    }
}
