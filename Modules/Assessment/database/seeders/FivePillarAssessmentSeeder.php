<?php

namespace Modules\Assessment\Database\Seeders;

use Illuminate\Database\Seeder;
use Modules\Assessment\Models\Assessment;
use Modules\Assessment\Models\AssessmentDomain;
use Modules\Assessment\Models\MaturityLevel;
use Modules\Assessment\Models\ScoreBand;
use Modules\Assessment\Models\ScoreRule;
use Modules\Assessment\Models\ScoreRuleOption;

class FivePillarAssessmentSeeder extends Seeder
{
    private const CODE = 'ORG_5PILLAR';

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
        $this->seedSampleScoreRules();

        $this->command->info('Seeded ORG_5PILLAR assessment config thành công.');
    }

    private function seedAssessment(): void
    {
        Assessment::withoutTenant()->firstOrCreate(
            ['assessment_code' => self::CODE],
            [
                'name'                => 'Khung đánh giá năng lực chuyển đổi số tổ chức (5-Pillar v1.0)',
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
                'domain_code' => 'P1_STRATEGY',
                'label'       => 'Chiến lược và Lãnh đạo',
                'weight'      => 0.2000,
                'min_score'   => 0,
                'max_score'   => 100,
                'sort_order'  => 1,
            ],
            [
                'domain_code' => 'P2_PROCESS',
                'label'       => 'Quy trình và Vận hành',
                'weight'      => 0.2500,
                'min_score'   => 0,
                'max_score'   => 100,
                'sort_order'  => 2,
            ],
            [
                'domain_code' => 'P3_DATA',
                'label'       => 'Dữ liệu và Quản trị dữ liệu',
                'weight'      => 0.2000,
                'min_score'   => 0,
                'max_score'   => 100,
                'sort_order'  => 3,
            ],
            [
                'domain_code' => 'P4_PEOPLE',
                'label'       => 'Nguồn Nhân lực',
                'weight'      => 0.1500,
                'min_score'   => 0,
                'max_score'   => 100,
                'sort_order'  => 4,
            ],
            [
                'domain_code' => 'P5_TECH',
                'label'       => 'Công nghệ và Đổi mới sáng tạo',
                'weight'      => 0.2000,
                'min_score'   => 0,
                'max_score'   => 100,
                'sort_order'  => 5,
            ],
        ];

        foreach ($domains as $d) {
            AssessmentDomain::create(array_merge(['assessment_code' => self::CODE], $d));
        }
    }

    private function seedMaturityLevels(): void
    {
        $levels = [
            ['level_code' => 'ORG_INIT',       'label' => 'Khởi đầu — Hoạt động rời rạc, thiếu quy trình',          'min_score' => 0,  'max_score' => 20,  'sort_order' => 1],
            ['level_code' => 'ORG_FORMING',    'label' => 'Hình thành — Có nhận thức, có kế hoạch ban đầu',          'min_score' => 21, 'max_score' => 40,  'sort_order' => 2],
            ['level_code' => 'ORG_DEVELOPING', 'label' => 'Phát triển — Bắt đầu triển khai, có kết quả bước đầu',   'min_score' => 41, 'max_score' => 60,  'sort_order' => 3],
            ['level_code' => 'ORG_ADVANCED',   'label' => 'Nâng cao — Quy trình chuẩn hóa, có cơ chế đo lường',    'min_score' => 61, 'max_score' => 80,  'sort_order' => 4],
            ['level_code' => 'ORG_LEADING',    'label' => 'Dẫn đầu — AI là năng lực cốt lõi, liên tục cải tiến',   'min_score' => 81, 'max_score' => 100, 'sort_order' => 5],
        ];

        foreach ($levels as $l) {
            MaturityLevel::create(array_merge(['assessment_code' => self::CODE], $l));
        }
    }

    private function seedScoreBands(): void
    {
        $bands = [
            ['band_code' => 'ORG_BAND_INIT',       'label' => 'Khởi đầu',   'min_score' => 0,  'max_score' => 20,  'lead_temperature' => 'cold', 'sort_order' => 1],
            ['band_code' => 'ORG_BAND_FORMING',    'label' => 'Hình thành', 'min_score' => 21, 'max_score' => 40,  'lead_temperature' => 'cold', 'sort_order' => 2],
            ['band_code' => 'ORG_BAND_DEVELOPING', 'label' => 'Phát triển', 'min_score' => 41, 'max_score' => 60,  'lead_temperature' => 'warm', 'sort_order' => 3],
            ['band_code' => 'ORG_BAND_ADVANCED',   'label' => 'Nâng cao',   'min_score' => 61, 'max_score' => 80,  'lead_temperature' => 'warm', 'sort_order' => 4],
            ['band_code' => 'ORG_BAND_LEADING',    'label' => 'Dẫn đầu',   'min_score' => 81, 'max_score' => 100, 'lead_temperature' => 'hot',  'sort_order' => 5],
        ];

        foreach ($bands as $b) {
            ScoreBand::create(array_merge(
                ['assessment_code' => self::CODE, 'is_dynamic' => false],
                ['default_min' => $b['min_score'], 'default_max' => $b['max_score']],
                $b
            ));
        }
    }

    // Seed 2 sample score rules with 0–5 Likert scale (P1.1.01 and P2.1.01)
    private function seedSampleScoreRules(): void
    {
        $this->ruleLikert5(
            fieldKey:    'p1_1_01_ai_awareness',
            domainCode:  'P1_STRATEGY',
            options:     [
                [0, 'Không biết AI là gì'],
                [1, 'Biết khái niệm cơ bản'],
                [2, 'Biết một số công cụ AI'],
                [3, 'Hiểu cách ứng dụng AI'],
                [4, 'Hiểu tác động chiến lược'],
                [5, 'Có khả năng dẫn dắt triển khai AI'],
            ]
        );

        $this->ruleLikert5(
            fieldKey:    'p2_1_01_process_standardization',
            domainCode:  'P2_PROCESS',
            options:     [
                [0, 'Chưa có quy trình nào'],
                [1, 'Có một vài hướng dẫn không chính thức'],
                [2, 'Có quy trình viết tay / Excel'],
                [3, 'Quy trình số hóa cơ bản'],
                [4, 'Quy trình chuẩn hóa và đo lường'],
                [5, 'Quy trình tự động hóa và liên tục cải tiến'],
            ]
        );
    }

    // Each Likert level maps to 0–100 scale in steps of 20
    private function ruleLikert5(string $fieldKey, string $domainCode, array $options): void
    {
        $rule = ScoreRule::create([
            'assessment_code'       => self::CODE,
            'field_key'             => $fieldKey,
            'feature_code'          => $fieldKey,
            'domain_code'           => $domainCode,
            'signal_flag'           => null,
            'score_if_true'         => 0,
            'score_if_false'        => 0,
            'condition_type'        => 'multi_choice',
            'question_scoring_type' => 'multi_choice',
            'is_active'             => true,
        ]);

        foreach ($options as $i => [$level, $label]) {
            ScoreRuleOption::create([
                'rule_id'      => $rule->id,
                'option_value' => (string) $level,
                'option_label' => $label,
                'score'        => $level * 20, // 0,20,40,60,80,100
                'signal_flag'  => null,
                'sort_order'   => $i,
            ]);
        }
    }
}
