<?php

namespace Modules\PensionCalculator\Database\Seeders;

use Illuminate\Database\Seeder;
use Modules\PensionCalculator\Enums\SupportGroupKey;
use Modules\PensionCalculator\Models\PensionParameterPeriod;
use Modules\PensionCalculator\Models\PensionPriceIndexCoefficient;
use Modules\PensionCalculator\Models\PensionRateBracket;
use Modules\PensionCalculator\Models\PensionSupportTier;

/**
 * spec/bhxh/PensionCalculator_Technical_Specification.md §8.3 — seed ĐÚNG số liệu đã đối
 * chiếu/xác minh trong spec (Nghị định 159/2025/NĐ-CP Điều 5, §2.2 sửa lỗi đánh máy
 * 46.800.000 của bhxh.md).
 *
 * §14 mục 6 (v1.2) — hệ số trượt giá ĐÃ có nguồn thật: Công văn 340/BHXH-CSXH ngày 03/02/2026
 * của BHXH Việt Nam (spec/bhxh/BHXHVN_340-BHXH-CSXH_03022026.pdf), cột "Hệ số điều chỉnh thu
 * nhập tháng đã đóng BHXH" (quy định tại khoản 2 Điều 10 Nghị định 159/2025/NĐ-CP — đúng công
 * thức §6.6). Cột "Hệ số điều chỉnh tiền lương" (Điều 16.1.a Nghị định 158/2025/NĐ-CP) là cho
 * BHXH BẮT BUỘC, ngoài phạm vi seed của module này (module không tự tính nhánh bắt buộc, §12).
 *
 * §14 mục 1 (v1.3) — tỷ lệ hưởng lương hưu ĐÃ có nguồn thật: `spec/bhxh/41-2024-qh15.pdf`
 * (Luật Bảo hiểm xã hội 2024, số 41/2024/QH15). Điều 66 (nhánh mixed/bắt buộc) và Điều 99
 * (nhánh thuần tự nguyện) dùng ĐÚNG CÙNG 1 công thức — xem seedRateBrackets().
 *
 * Chạy: php artisan db:seed --class="Modules\PensionCalculator\Database\Seeders\PensionCalculatorDemoParameterSeeder"
 */
class PensionCalculatorDemoParameterSeeder extends Seeder
{
    /**
     * Công văn 340/BHXH-CSXH 03/02/2026 — cột "Hệ số điều chỉnh thu nhập tháng đã đóng BHXH",
     * áp dụng cho hồ sơ giải quyết năm 2026 (settlement_year=2026). Không có dòng trước 2008 vì
     * BHXH tự nguyện chỉ có hiệu lực từ 01/01/2008 (Luật BHXH 2006) — bảng gốc cũng để trống ô
     * đó, KHÔNG phải thiếu sót cần bổ sung.
     */
    private const SETTLEMENT_YEAR_2026_COEFFICIENTS = [
        2008 => 2.29, 2009 => 2.14, 2010 => 1.96, 2011 => 1.65, 2012 => 1.51,
        2013 => 1.42, 2014 => 1.36, 2015 => 1.36, 2016 => 1.32, 2017 => 1.28,
        2018 => 1.23, 2019 => 1.20, 2020 => 1.16, 2021 => 1.14, 2022 => 1.11,
        2023 => 1.07, 2024 => 1.03, 2025 => 1.00, 2026 => 1.00,
    ];

    public function run(): void
    {
        $period = PensionParameterPeriod::firstOrCreate(
            ['effective_from' => '2025-07-01'],
            [
                'rural_poverty_line' => 1_500_000,
                'reference_level' => 2_340_000,
                'contribution_rate_percent' => 22.00,
                'ceiling_multiplier' => 20,
                'source_document' => 'Nghị định 159/2025/NĐ-CP Điều 5; Luật Bảo hiểm xã hội 2024 Điều 141',
            ]
        );

        $tiers = [
            SupportGroupKey::PoorHousehold->value => 50.00,
            SupportGroupKey::NearPoorHousehold->value => 40.00,
            SupportGroupKey::EthnicMinority->value => 30.00,
            SupportGroupKey::Other->value => 20.00,
        ];

        foreach ($tiers as $groupKey => $supportPercent) {
            PensionSupportTier::firstOrCreate(
                ['period_id' => $period->id, 'group_key' => $groupKey],
                ['support_percent' => $supportPercent]
            );
        }

        foreach (self::SETTLEMENT_YEAR_2026_COEFFICIENTS as $contributionYear => $coefficient) {
            PensionPriceIndexCoefficient::firstOrCreate(
                ['settlement_year' => 2026, 'contribution_year' => $contributionYear],
                ['coefficient' => $coefficient, 'source_document' => 'Công văn 340/BHXH-CSXH ngày 03/02/2026 của Bảo hiểm xã hội Việt Nam']
            );
        }

        $this->seedRateBrackets();

        $this->command?->info('  ✓ PensionCalculator: seeded period 2025-07-01 + 4 support tiers + '.count(self::SETTLEMENT_YEAR_2026_COEFFICIENTS).' hệ số trượt giá + 3 bậc tỷ lệ hưởng lương hưu (Luật BHXH 2024 Điều 66/99).');
    }

    /**
     * Luật Bảo hiểm xã hội 2024 (41/2024/QH15) Điều 66 (BHXH bắt buộc, nhánh mixed a/b, §6.9) và
     * Điều 99 (BHXH tự nguyện, nhánh thuần tự nguyện c/d) — CÙNG 1 công thức từng chữ:
     *   - Nữ: 45% mức bình quân tương ứng 15 năm đóng, +2%/năm tiếp theo, tối đa 75%.
     *   - Nam: 45% mức bình quân tương ứng 20 năm đóng, +2%/năm tiếp theo, tối đa 75%.
     *   - Riêng nam có 15-19 năm (dưới 20 năm): 40% tương ứng 15 năm, +1%/năm tiếp theo — bậc
     *     này TỰ NHIÊN ngừng áp dụng ở năm 20 vì pensionRateFor() chọn bậc có min_years_for_base
     *     _rate LỚN NHẤT ≤ years, và bậc "nam 20 năm" sẽ thắng bậc "nam 15 năm" từ năm 20 trở đi
     *     (40%+1%×5=45%, khớp đúng điểm nối liền mạch với bậc 45% nền của mốc 20 năm).
     * Vì 2 Điều dùng chung số liệu, module seed 1 bộ DÙNG CHUNG cho cả 2 nhánh (schema chỉ phân
     * biệt theo gender/years, không phân biệt nguồn Điều 66 hay Điều 99).
     */
    private function seedRateBrackets(): void
    {
        $brackets = [
            ['gender' => 'female', 'min_years_for_base_rate' => 15, 'base_rate_percent' => 45.00, 'increment_percent_per_year' => 2.00, 'max_rate_percent' => 75.00],
            ['gender' => 'male', 'min_years_for_base_rate' => 15, 'base_rate_percent' => 40.00, 'increment_percent_per_year' => 1.00, 'max_rate_percent' => 75.00],
            ['gender' => 'male', 'min_years_for_base_rate' => 20, 'base_rate_percent' => 45.00, 'increment_percent_per_year' => 2.00, 'max_rate_percent' => 75.00],
        ];

        foreach ($brackets as $bracket) {
            PensionRateBracket::firstOrCreate(
                [
                    'gender' => $bracket['gender'],
                    'min_years_for_base_rate' => $bracket['min_years_for_base_rate'],
                    'effective_from' => '2025-07-01',
                ],
                [
                    'base_rate_percent' => $bracket['base_rate_percent'],
                    'increment_percent_per_year' => $bracket['increment_percent_per_year'],
                    'max_rate_percent' => $bracket['max_rate_percent'],
                    'source_document' => 'Luật Bảo hiểm xã hội 2024 (41/2024/QH15) Điều 66 (bắt buộc) / Điều 99 (tự nguyện)',
                ]
            );
        }
    }
}
