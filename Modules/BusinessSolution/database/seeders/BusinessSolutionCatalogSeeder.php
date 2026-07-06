<?php

namespace Modules\BusinessSolution\Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Str;
use Modules\BusinessSolution\Models\BusinessSolution;
use Modules\BusinessSolution\Models\Vertical;

/**
 * Đăng ký 3 Business Solution bespoke hiện có làm dữ liệu khởi tạo (spec §1.9).
 * Chỉ mang tính khai báo/catalog — KHÔNG thay đổi logic vận hành của
 * Modules\Deployment / Modules\OcopRubric / Modules\Assessment ở giai đoạn này.
 */
class BusinessSolutionCatalogSeeder extends Seeder
{
    private array $solutions = [
        [
            'code'              => 'AI-TXNG',
            'vertical_code'     => 'agriculture',
            'name'              => 'AI Truy xuất nguồn gốc',
            'short_description' => 'Truy xuất nguồn gốc nông sản — tương ứng Modules\\Deployment (+ Project, Survey).',
            'target_customers'  => ['htx', 'sme'],
        ],
        [
            'code'              => 'AI-OCOP',
            'vertical_code'     => 'agriculture',
            'name'              => 'AI Chấm điểm OCOP',
            'short_description' => 'Bộ tiêu chí OCOP (QĐ 26/2026/QĐ-TTg) — tương ứng Modules\\OcopRubric.',
            'target_customers'  => ['htx', 'sme'],
        ],
        [
            'code'              => 'AI-WORKFORCE',
            'vertical_code'     => 'workforce',
            'name'              => 'AI Đánh giá nhân sự',
            'short_description' => 'Khảo sát & chấm điểm đánh giá nhân sự — tương ứng Modules\\Assessment.',
            'target_customers'  => ['sme'],
        ],
    ];

    public function run(): void
    {
        foreach ($this->solutions as $solution) {
            $vertical = Vertical::where('code', $solution['vertical_code'])->firstOrFail();

            BusinessSolution::updateOrCreate(
                ['code' => $solution['code']],
                [
                    'vertical_id'       => $vertical->id,
                    'name'              => $solution['name'],
                    'slug'              => Str::slug($solution['name']),
                    'short_description' => $solution['short_description'],
                    'target_customers'  => $solution['target_customers'],
                    'status'            => 'published',
                    'visibility'        => 'public',
                ],
            );
        }

        $this->command?->info('  ✓ BusinessSolution catalog seeded (3 bespoke solutions).');
    }
}
