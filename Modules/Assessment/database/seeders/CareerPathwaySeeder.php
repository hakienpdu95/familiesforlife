<?php

namespace Modules\Assessment\Database\Seeders;

use Illuminate\Database\Seeder;
use Modules\Assessment\Models\CareerPathwayStep;

class CareerPathwaySeeder extends Seeder
{
    public function run(): void
    {
        if (CareerPathwayStep::where('organization_id', null)->exists()) {
            $this->command->info('CareerPathwaySteps (global) đã tồn tại, bỏ qua.');
            return;
        }

        // Lộ trình TDWCF: Digital Beginner → Digital Leader
        $steps = [
            [
                'from_level'                   => 'DIGITAL_BEGINNER',
                'to_level'                     => 'DIGITAL_AWARE',
                'step_order'                   => 1,
                'title'                        => 'Xây dựng nền tảng số cơ bản',
                'description'                  => 'Làm quen với công cụ AI, thực hành Sandbox Foundation, hoàn thành bộ khảo sát TDWCF lần đầu.',
                'required_cert_code'           => null,
                'recommended_kc_tag'           => 'digital_literacy|ai_basics',
                'recommended_sandbox_env_code' => 'AI_OFFICE_F1',
                'estimated_weeks'              => 4,
            ],
            [
                'from_level'                   => 'DIGITAL_AWARE',
                'to_level'                     => 'DIGITAL_PRACTITIONER',
                'step_order'                   => 2,
                'title'                        => 'Thực hành và đạt chứng nhận Foundation',
                'description'                  => 'Hoàn thành ít nhất 2 Sandbox sessions, đạt điểm TDWCF ≥ 41, nhận chứng nhận AI Workforce Foundation.',
                'required_cert_code'           => 'AI_ADMIN_FOUNDATION',
                'recommended_kc_tag'           => 'prompt_engineering|workflow_basics',
                'recommended_sandbox_env_code' => 'AI_WORKFLOW_F1',
                'estimated_weeks'              => 8,
            ],
            [
                'from_level'                   => 'DIGITAL_PRACTITIONER',
                'to_level'                     => 'DIGITAL_PROFESSIONAL',
                'step_order'                   => 3,
                'title'                        => 'Nâng cao và đạt chứng nhận Practitioner',
                'description'                  => 'KPI ≥ 70%, có ít nhất 1 case study portfolio, đạt điểm TDWCF ≥ 61.',
                'required_cert_code'           => 'AI_ADMIN_PRACTITIONER',
                'recommended_kc_tag'           => 'ai_workflow_design|data_analysis',
                'recommended_sandbox_env_code' => 'AI_DATA_F1',
                'estimated_weeks'              => 12,
            ],
            [
                'from_level'                   => 'DIGITAL_PROFESSIONAL',
                'to_level'                     => 'DIGITAL_LEADER',
                'step_order'                   => 4,
                'title'                        => 'Trở thành chuyên gia và đạt chứng nhận Professional',
                'description'                  => 'Sandbox ≥ 20 giờ, Impact Score > 0, đạt điểm TDWCF ≥ 76.',
                'required_cert_code'           => 'AI_ADMIN_PROFESSIONAL',
                'recommended_kc_tag'           => 'ai_strategy|change_management|impact_measurement',
                'recommended_sandbox_env_code' => 'AI_LEADERSHIP_F1',
                'estimated_weeks'              => 16,
            ],
            [
                'from_level'                   => 'DIGITAL_LEADER',
                'to_level'                     => 'DIGITAL_LEADER',
                'step_order'                   => 5,
                'title'                        => 'Dẫn dắt chuyển đổi số — Chứng nhận Leader',
                'description'                  => 'Portfolio được duyệt, TDWCF ≥ 91, dẫn dắt ít nhất 1 sáng kiến AI trong tổ chức.',
                'required_cert_code'           => 'AI_ADMIN_LEADER',
                'recommended_kc_tag'           => 'ai_leadership|organization_transformation',
                'recommended_sandbox_env_code' => 'AI_LEADERSHIP_F1',
                'estimated_weeks'              => 24,
            ],
        ];

        foreach ($steps as $step) {
            CareerPathwayStep::create(array_merge(['organization_id' => null, 'is_active' => true], $step));
        }

        $this->command->info('Seeded CareerPathwaySteps (global) thành công — ' . count($steps) . ' bước.');
    }
}
