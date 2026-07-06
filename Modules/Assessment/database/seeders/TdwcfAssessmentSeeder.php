<?php

namespace Modules\Assessment\Database\Seeders;

use Illuminate\Database\Seeder;
use Modules\Assessment\Models\Assessment;
use Modules\Assessment\Models\AssessmentDomain;
use Modules\Assessment\Models\MaturityLevel;
use Modules\Assessment\Models\ScoreBand;

class TdwcfAssessmentSeeder extends Seeder
{
    private const CODE = 'TDWCF';

    public function run(): void
    {
        if (Assessment::withoutTenant()->where('assessment_code', self::CODE)->exists()) {
            $this->command->info('Assessment "' . self::CODE . '" đã tồn tại, bỏ qua.');
            return;
        }

        $this->seedAssessment();
        $this->seedDomains();
        $this->seedMaturityLevels();
        $this->seedScoreBands();

        $this->command->info('Seeded TDWCF assessment config thành công.');
    }

    private function seedAssessment(): void
    {
        Assessment::withoutTenant()->firstOrCreate(
            ['assessment_code' => self::CODE],
            [
                'name'                => 'Khung năng lực số theo vị trí việc làm (TDWCF v1.0)',
                'version'             => '1.0',
                'is_active'           => true,
                'has_scoring'         => true,
                'aggregation_model'   => 'weighted_domain',
                'classification_type' => 'score_band',
                'source_type'         => 'global_template',
            ]
        );
    }

    private function seedDomains(): void
    {
        $domains = [
            [
                'domain_code' => 'D1_DIGITAL_LITERACY',
                'label'       => 'Digital Literacy — Năng lực số nền tảng',
                'weight'      => 0.1500,
                'min_score'   => 0,
                'max_score'   => 100,
                'sort_order'  => 1,
            ],
            [
                'domain_code' => 'D2_DATA_LITERACY',
                'label'       => 'Data Literacy — Năng lực dữ liệu',
                'weight'      => 0.1500,
                'min_score'   => 0,
                'max_score'   => 100,
                'sort_order'  => 2,
            ],
            [
                'domain_code' => 'D3_AI_LITERACY',
                'label'       => 'AI Literacy — Năng lực trí tuệ nhân tạo',
                'weight'      => 0.2000,
                'min_score'   => 0,
                'max_score'   => 100,
                'sort_order'  => 3,
            ],
            [
                'domain_code' => 'D4_WORKFLOW',
                'label'       => 'Workflow & Automation — Quy trình và tự động hóa',
                'weight'      => 0.2000,
                'min_score'   => 0,
                'max_score'   => 100,
                'sort_order'  => 4,
            ],
            [
                'domain_code' => 'D5_INNOVATION',
                'label'       => 'Innovation & Problem Solving — Đổi mới sáng tạo',
                'weight'      => 0.1500,
                'min_score'   => 0,
                'max_score'   => 100,
                'sort_order'  => 5,
            ],
            [
                'domain_code' => 'D6_PERFORMANCE',
                'label'       => 'Work Performance & Impact — Hiệu suất và tác động',
                'weight'      => 0.1500,
                'min_score'   => 0,
                'max_score'   => 100,
                'sort_order'  => 6,
            ],
        ];

        foreach ($domains as $d) {
            AssessmentDomain::create(array_merge(['assessment_code' => self::CODE], $d));
        }
    }

    private function seedMaturityLevels(): void
    {
        $levels = [
            ['level_code' => 'DIGITAL_BEGINNER',      'label' => 'Digital Beginner — Người mới bắt đầu số',      'min_score' => 0,  'max_score' => 20,  'sort_order' => 1],
            ['level_code' => 'DIGITAL_AWARE',         'label' => 'Digital Aware — Có nhận thức số',               'min_score' => 21, 'max_score' => 40,  'sort_order' => 2],
            ['level_code' => 'DIGITAL_PRACTITIONER',  'label' => 'Digital Practitioner — Người thực hành số',     'min_score' => 41, 'max_score' => 60,  'sort_order' => 3],
            ['level_code' => 'DIGITAL_PROFESSIONAL',  'label' => 'Digital Professional — Chuyên nghiệp số',       'min_score' => 61, 'max_score' => 80,  'sort_order' => 4],
            ['level_code' => 'DIGITAL_LEADER',        'label' => 'Digital Transformation Leader — Dẫn dắt CĐS',  'min_score' => 81, 'max_score' => 100, 'sort_order' => 5],
        ];

        foreach ($levels as $l) {
            MaturityLevel::create(array_merge(['assessment_code' => self::CODE], $l));
        }
    }

    private function seedScoreBands(): void
    {
        $bands = [
            ['band_code' => 'BAND_BEGINNER',      'label' => 'Sơ cấp',   'min_score' => 0,  'max_score' => 20,  'lead_temperature' => 'cold', 'sort_order' => 1],
            ['band_code' => 'BAND_AWARE',         'label' => 'Cơ bản',   'min_score' => 21, 'max_score' => 40,  'lead_temperature' => 'cold', 'sort_order' => 2],
            ['band_code' => 'BAND_PRACTITIONER',  'label' => 'Trung cấp','min_score' => 41, 'max_score' => 60,  'lead_temperature' => 'warm', 'sort_order' => 3],
            ['band_code' => 'BAND_PROFESSIONAL',  'label' => 'Nâng cao', 'min_score' => 61, 'max_score' => 80,  'lead_temperature' => 'warm', 'sort_order' => 4],
            ['band_code' => 'BAND_LEADER',        'label' => 'Chuyên gia','min_score' => 81, 'max_score' => 100, 'lead_temperature' => 'hot', 'sort_order' => 5],
        ];

        foreach ($bands as $b) {
            ScoreBand::create(array_merge(
                ['assessment_code' => self::CODE, 'is_dynamic' => false],
                ['default_min' => $b['min_score'], 'default_max' => $b['max_score']],
                $b
            ));
        }
    }
}
