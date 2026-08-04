<?php

namespace Modules\PensionCalculator\Features\ParameterManagement\Actions;

use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
use Lorisleiva\Actions\Concerns\AsAction;
use Modules\PensionCalculator\Models\PensionPriceIndexCoefficient;

/**
 * spec/bhxh/PensionCalculator_Technical_Specification.md §9.2 — hệ số trượt giá nhập theo LÔ 1
 * `settlement_year` (giống cách BHXH Việt Nam công bố 1 bảng/năm hưởng, liệt kê hệ số của TỪNG
 * năm đã đóng trước đó), form nhiều dòng chứ không phải 1 dòng/lần.
 */
class SavePriceIndexCoefficientAction
{
    use AsAction;

    /**
     * @param  array{settlement_year:int, source_document:string, created_by:?int,
     *     rows: array<int, array{contribution_year:int, coefficient:float}>} $data
     * @return array<int, PensionPriceIndexCoefficient>
     */
    public function handle(array $data): array
    {
        $settlementYear = $data['settlement_year'];

        $existingYears = PensionPriceIndexCoefficient::where('settlement_year', $settlementYear)
            ->whereIn('contribution_year', array_column($data['rows'], 'contribution_year'))
            ->pluck('contribution_year')
            ->all();

        if (! empty($existingYears)) {
            throw ValidationException::withMessages([
                'rows' => 'Đã có hệ số cho năm giải quyết '.$settlementYear.', năm đóng: '.implode(', ', $existingYears).' — dữ liệu đã có hiệu lực không được sửa (§9.1), nếu cần đính chính hãy nhập giai đoạn/lô mới.',
            ]);
        }

        return DB::transaction(function () use ($data, $settlementYear) {
            $created = [];

            foreach ($data['rows'] as $row) {
                $created[] = PensionPriceIndexCoefficient::create([
                    'settlement_year' => $settlementYear,
                    'contribution_year' => $row['contribution_year'],
                    'coefficient' => $row['coefficient'],
                    'source_document' => $data['source_document'],
                    'created_by' => $data['created_by'] ?? null,
                ]);
            }

            return $created;
        });
    }
}
