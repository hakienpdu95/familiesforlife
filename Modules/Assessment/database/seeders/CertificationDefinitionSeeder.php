<?php

namespace Modules\Assessment\Database\Seeders;

use Illuminate\Database\Seeder;
use Modules\Assessment\Models\CertificationDefinition;

class CertificationDefinitionSeeder extends Seeder
{
    public function run(): void
    {
        if (CertificationDefinition::where('organization_id', null)->exists()) {
            $this->command->info('CertificationDefinitions (global) đã tồn tại, bỏ qua.');
            return;
        }

        $this->seedGlobalDefinitions();
        $this->command->info('Seeded global CertificationDefinitions thành công.');
    }

    private function seedGlobalDefinitions(): void
    {
        $levels = [
            [
                'level_code'                  => 'FOUNDATION',
                'level_order'                 => 1,
                'validity_months'             => 24,
                'min_workforce_score'         => 40.00,
                'min_kpi_achievement_pct'     => null,
                'min_sandbox_hours'           => null,
                'min_sandbox_score'           => null,
                'requires_impact_score'       => false,
                'requires_portfolio_approval' => false,
            ],
            [
                'level_code'                  => 'PRACTITIONER',
                'level_order'                 => 2,
                'validity_months'             => 24,
                'min_workforce_score'         => 61.00,
                'min_kpi_achievement_pct'     => 70.00,
                'min_sandbox_hours'           => null,
                'min_sandbox_score'           => null,
                'requires_impact_score'       => false,
                'requires_portfolio_approval' => false,
            ],
            [
                'level_code'                  => 'PROFESSIONAL',
                'level_order'                 => 3,
                'validity_months'             => 36,
                'min_workforce_score'         => 76.00,
                'min_kpi_achievement_pct'     => null,
                'min_sandbox_hours'           => 20,
                'min_sandbox_score'           => null,
                'requires_impact_score'       => true,
                'requires_portfolio_approval' => false,
            ],
            [
                'level_code'                  => 'LEADER',
                'level_order'                 => 4,
                'validity_months'             => 36,
                'min_workforce_score'         => 91.00,
                'min_kpi_achievement_pct'     => null,
                'min_sandbox_hours'           => null,
                'min_sandbox_score'           => null,
                'requires_impact_score'       => false,
                'requires_portfolio_approval' => true,
            ],
        ];

        $types = [
            ['code' => 'AI_ADMIN',   'name' => 'AI Administrative Officer',  'target' => 'Cán bộ hành chính / Văn phòng'],
            ['code' => 'AI_HR',      'name' => 'AI HR Practitioner',          'target' => 'Nhân sự'],
            ['code' => 'AI_SALES',   'name' => 'AI Sales Practitioner',       'target' => 'Kinh doanh / Sales'],
            ['code' => 'AI_FINANCE', 'name' => 'AI Finance Practitioner',     'target' => 'Tài chính / Kế toán'],
            ['code' => 'AI_DATA',    'name' => 'AI Data Operator',            'target' => 'Nhập liệu / Xử lý dữ liệu'],
            ['code' => 'AI_MANAGER', 'name' => 'AI Workforce Manager',        'target' => 'Quản lý nguồn nhân lực'],
            ['code' => 'AI_LEADER',  'name' => 'AI Transformation Leader',    'target' => 'Lãnh đạo chuyển đổi số'],
        ];

        foreach ($types as $type) {
            foreach ($levels as $level) {
                $certCode = strtoupper(str_replace(' ', '_', "{$type['code']}_{$level['level_code']}"));
                CertificationDefinition::create(array_merge(
                    [
                        'organization_id' => null,
                        'cert_code'       => $certCode,
                        'cert_type_code'  => $type['code'],
                        'name'            => "{$type['name']} — {$level['level_code']}",
                        'description'     => "Chứng nhận {$level['level_code']} cho vị trí {$type['target']}",
                        'is_active'       => true,
                    ],
                    $level
                ));
            }
        }
    }
}
