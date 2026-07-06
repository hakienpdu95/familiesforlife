<?php

namespace Modules\Survey\Database\Seeders;

use Illuminate\Database\Seeder;
use Modules\Assessment\Models\Assessment;
use Modules\Assessment\Models\AssessmentDomain;
use Modules\Assessment\Models\MaturityLevel;
use Modules\Assessment\Models\PainPointRule;
use Modules\Assessment\Models\RecommendationRule;
use Modules\Assessment\Models\RoadmapMilestone;
use Modules\Assessment\Models\RoadmapPhase;
use Modules\Assessment\Models\ScoreBand;
use Modules\Assessment\Models\ScoreRule;
use Modules\Assessment\Models\ScoreRuleOption;

class ScoringConfigSeeder extends Seeder
{
    private const CODE = 'ai_workflow_v1';

    public function run(): void
    {
        if (AssessmentDomain::where('assessment_code', self::CODE)->exists()) {
            $this->command->info('ScoringConfig "' . self::CODE . '" đã tồn tại, bỏ qua.');
            return;
        }

        $this->seedAssessment();
        $this->seedDomains();
        $this->seedScoreBands();
        $this->seedMaturityLevels();
        $this->seedScoreRules();
        $this->seedPainPointRules();
        $this->seedRecommendationRules();
        $this->seedRoadmap();

        $this->command->info('Seeded scoring config "' . self::CODE . '" thành công.');
    }

    // ── Assessment (config hub) ───────────────────────────────────────────────

    private function seedAssessment(): void
    {
        Assessment::firstOrCreate(
            ['assessment_code' => self::CODE],
            [
                'name'                => 'AI Readiness & Workflow Assessment',
                'version'             => '1.0',
                'is_active'           => true,
                'has_scoring'         => true,
                'aggregation_model'   => 'weighted_domain',
                'classification_type' => 'score_band',
            ]
        );
    }

    // ── Domains ───────────────────────────────────────────────────────────────

    private function seedDomains(): void
    {
        $domains = [
            ['domain_code' => 'workflow', 'label' => 'Quy trình & Vận hành',  'weight' => 0.2500, 'min_score' => -67, 'max_score' => 58, 'sort_order' => 1],
            ['domain_code' => 'sales',    'label' => 'Bán hàng & Khách hàng', 'weight' => 0.2000, 'min_score' => -55, 'max_score' => 60, 'sort_order' => 2],
            ['domain_code' => 'hr',       'label' => 'Nhân sự & Đào tạo',     'weight' => 0.1500, 'min_score' => -43, 'max_score' => 55, 'sort_order' => 3],
            ['domain_code' => 'data',     'label' => 'Dữ liệu & Hệ thống',    'weight' => 0.2000, 'min_score' => -50, 'max_score' => 60, 'sort_order' => 4],
            ['domain_code' => 'ai',       'label' => 'AI Readiness',           'weight' => 0.2000, 'min_score' => -57, 'max_score' => 60, 'sort_order' => 5],
        ];

        foreach ($domains as $d) {
            AssessmentDomain::create(array_merge(['assessment_code' => self::CODE], $d));
        }
    }

    // ── Score Bands (Tầng 3 — Classification) ────────────────────────────────

    private function seedScoreBands(): void
    {
        $bands = [
            [
                'band_code'        => 'MANUAL_OPERATION',
                'label'            => 'Vận hành thủ công',
                'description'      => 'Doanh nghiệp đang vận hành chủ yếu thủ công, thiếu quy trình chuẩn và công cụ hỗ trợ. Ưu tiên số hóa nền tảng trước khi ứng dụng AI.',
                'min_score'        => 0,
                'max_score'        => 30,
                'default_min'      => 0,
                'default_max'      => 30,
                'lead_temperature' => 'cold',
                'sort_order'       => 1,
            ],
            [
                'band_code'        => 'DIGITAL_FOUNDATION',
                'label'            => 'Nền tảng số cơ bản',
                'description'      => 'Doanh nghiệp đã có một số công cụ số hóa nhưng chưa đồng bộ. Cần chuẩn hóa quy trình và tích hợp dữ liệu trước khi triển khai AI.',
                'min_score'        => 31,
                'max_score'        => 60,
                'default_min'      => 31,
                'default_max'      => 60,
                'lead_temperature' => 'warm',
                'sort_order'       => 2,
            ],
            [
                'band_code'        => 'AI_READY',
                'label'            => 'Sẵn sàng triển khai AI',
                'description'      => 'Doanh nghiệp có nền tảng vận hành tốt, dữ liệu tương đối tập trung. Sẵn sàng thử nghiệm và triển khai AI vào các nghiệp vụ cụ thể.',
                'min_score'        => 61,
                'max_score'        => 80,
                'default_min'      => 61,
                'default_max'      => 80,
                'lead_temperature' => 'hot',
                'sort_order'       => 3,
            ],
            [
                'band_code'        => 'AI_TRANSFORMATION',
                'label'            => 'Chuyển đổi AI toàn diện',
                'description'      => 'Doanh nghiệp có quy trình chuẩn, dữ liệu tập trung và đội ngũ sẵn sàng. Có thể triển khai AI toàn diện để tạo lợi thế cạnh tranh bền vững.',
                'min_score'        => 81,
                'max_score'        => 100,
                'default_min'      => 81,
                'default_max'      => 100,
                'lead_temperature' => 'hot',
                'sort_order'       => 4,
            ],
        ];

        foreach ($bands as $b) {
            ScoreBand::create(array_merge(['assessment_code' => self::CODE], $b));
        }
    }

    // ── Maturity Levels (backward compat với schema cũ) ──────────────────────

    private function seedMaturityLevels(): void
    {
        $levels = [
            ['level_code' => 'MANUAL_OPERATION',  'label' => 'Vận hành thủ công',          'description' => 'Doanh nghiệp đang vận hành chủ yếu thủ công.', 'min_score' => 0,  'max_score' => 30,  'sort_order' => 1, 'lead_temperature' => 'cold'],
            ['level_code' => 'DIGITAL_FOUNDATION', 'label' => 'Nền tảng số cơ bản',          'description' => 'Đã có một số công cụ số hóa nhưng chưa đồng bộ.', 'min_score' => 31, 'max_score' => 60,  'sort_order' => 2, 'lead_temperature' => 'warm'],
            ['level_code' => 'AI_READY',           'label' => 'Sẵn sàng triển khai AI',     'description' => 'Nền tảng vận hành tốt, dữ liệu tương đối tập trung.', 'min_score' => 61, 'max_score' => 80,  'sort_order' => 3, 'lead_temperature' => 'hot'],
            ['level_code' => 'AI_TRANSFORMATION',  'label' => 'Chuyển đổi AI toàn diện',    'description' => 'Quy trình chuẩn, dữ liệu tập trung, đội ngũ sẵn sàng.', 'min_score' => 81, 'max_score' => 100, 'sort_order' => 4, 'lead_temperature' => 'hot'],
        ];

        foreach ($levels as $l) {
            MaturityLevel::create(array_merge(['assessment_code' => self::CODE], $l));
        }
    }

    // ── Score Rules ───────────────────────────────────────────────────────────

    private function seedScoreRules(): void
    {
        // Workflow domain
        $this->ruleChoice('existing_systems', 'workflow', [
            ['sop',            'Có SOP / tài liệu quy trình',     +15, 'HAS_SOP'],
            ['workflow',       'Phần mềm quản lý workflow',        +10, null],
            ['dashboard',      'Dashboard theo dõi hiệu suất',     +10, null],
            ['approval',       'Quy trình phê duyệt nội bộ',       +8,  null],
        ]);
        $this->ruleChoice('workflow_mode', 'workflow', [
            ['standard_process', 'Theo quy trình chuẩn',           +15, null],
            ['by_experience',    'Theo kinh nghiệm cá nhân',        -5,  null],
            ['by_direction',     'Theo chỉ đạo trực tiếp của sếp', -5,  null],
            ['unclear',          'Chưa rõ ràng / mỗi người một kiểu', -10, null],
        ]);
        $this->ruleChoice('current_problems', 'workflow', [
            ['hard_to_control', 'Khó kiểm soát tiến độ',           -15, 'CEO_HARD_TO_CONTROL'],
            ['repeated_errors', 'Lỗi lặp đi lặp lại',              -12, null],
            ['no_sop',          'Không có quy trình chuẩn',         -15, 'NO_SOP'],
            ['ceo_no_control',  'Sếp không nắm được tình hình',     -15, 'CEO_HARD_TO_CONTROL'],
        ]);
        $this->ruleChoice('staff_leave_impact', 'workflow', [
            ['very_high',  'Rất cao — tê liệt hoạt động',          -15, 'KEY_PERSON_DEPENDENCY'],
            ['medium',     'Trung bình — chậm một thời gian',       -5,  null],
            ['low',        'Thấp — xử lý được',                    +5,  null],
            ['negligible', 'Không đáng kể',                         +10, null],
        ]);

        // Sales domain
        $this->ruleChoice('using_crm', 'sales', [
            ['yes', 'Có, đang dùng CRM',     +20, 'HAS_CRM'],
            ['no',  'Không dùng CRM',        -10, null],
        ]);
        $this->ruleChoice('existing_systems', 'sales', [
            ['crm', 'CRM',                   +10, 'HAS_CRM'],
        ]);
        $this->ruleChoice('lead_management_tool', 'sales', [
            ['crm',          'CRM chuyên dụng',    +15, 'HAS_CRM'],
            ['excel',        'Excel',              +5,  null],
            ['google_sheet', 'Google Sheet',       +5,  null],
            ['zalo',         'Zalo / nhắn tin',    0,   null],
            ['none',         'Không theo dõi',     -12, 'NO_FOLLOWUP'],
        ]);
        $this->ruleChoice('sales_problems', 'sales', [
            ['no_follow_reminder',  'Không có nhắc nhở follow-up',   -12, 'LEAD_LOSS'],
            ['no_sale_metrics',     'Không đo KPI sales',            -10, 'NO_KPI_SALES'],
            ['data_loss_on_resign', 'Mất dữ liệu khi nhân viên nghỉ',-15, 'DATA_LOSS_ON_RESIGN'],
            ['lost_customers',      'Mất khách hàng không rõ lý do', -18, 'LEAD_LOSS'],
        ]);
        $this->ruleChoice('ceo_realtime_access', 'sales', [
            ['revenue',         'Doanh thu theo thời gian thực',    +10, null],
            ['conversion_rate', 'Tỷ lệ chốt đơn',                  +15, null],
            ['sale_kpi',        'KPI của từng nhân viên sales',     +10, null],
        ]);

        // HR domain
        $this->ruleChoice('onboarding_checklist', 'hr', [
            ['full',    'Đầy đủ, chuẩn hóa',                       +15, 'HAS_ONBOARDING'],
            ['partial', 'Có một phần',                              +5,  null],
            ['none',    'Không có',                                 -10, null],
        ]);
        $this->ruleChoice('kpi_system', 'hr', [
            ['has_kpi',           'Có hệ thống KPI rõ ràng',        +15, 'HAS_HR_KPI'],
            ['has_task_mgmt',     'Có quản lý công việc',           +10, null],
            ['has_periodic_eval', 'Có đánh giá định kỳ',            +10, null],
            ['unclear_resp',      'Trách nhiệm không rõ',           -8,  null],
        ]);
        $this->ruleChoice('hr_problems', 'hr', [
            ['new_staff_struggle',  'Nhân viên mới mất nhiều thời gian', -15, null],
            ['depend_on_old_staff', 'Phụ thuộc người có kinh nghiệm',    -18, 'TRAINING_DEPENDENCY'],
            ['no_training_docs',    'Không có tài liệu đào tạo',         -10, null],
            ['manual_training',     'Đào tạo hoàn toàn thủ công',        -10, null],
        ]);
        $this->ruleChoice('existing_systems', 'hr', [
            ['sop',      'Có SOP',                                  +15, null],
            ['workflow', 'Có workflow',                             +10, null],
            ['kpi',      'Có hệ thống KPI',                        +15, 'HAS_HR_KPI'],
        ]);

        // Data domain
        $this->ruleChoice('data_centralized', 'data', [
            ['fully_centralized',     'Tập trung hoàn toàn',         +20, 'DATA_CENTRALIZED'],
            ['partially_centralized', 'Một phần tập trung',          +8,  null],
            ['not_centralized',       'Chưa tập trung',              -20, 'DATA_FRAGMENTED'],
            ['very_scattered',        'Rất phân tán',                -20, 'DATA_FRAGMENTED'],
        ]);
        $this->ruleChoice('data_problems', 'data', [
            ['duplicate',  'Dữ liệu trùng lặp',                    -10, null],
            ['data_loss',  'Mất dữ liệu',                          -12, null],
            ['wrong_data', 'Dữ liệu sai',                          -15, null],
            ['no_backup',  'Không có backup',                       -15, 'NO_BACKUP'],
        ]);
        $this->ruleChoice('data_access_control', 'data', [
            ['full',    'Phân quyền đầy đủ',                        +15, null],
            ['partial', 'Có một phần',                              +5,  null],
            ['none',    'Không phân quyền',                         -8,  null],
        ]);
        $this->ruleChoice('realtime_reports', 'data', [
            ['full',    'Có báo cáo realtime',                      +15, null],
            ['partial', 'Một số báo cáo',                          +5,  null],
            ['none',    'Không có',                                 -5,  null],
        ]);
        $this->ruleChoice('existing_systems', 'data', [
            ['dashboard',      'Dashboard',                         +10, null],
            ['access_control', 'Phân quyền truy cập',              +10, null],
        ]);

        // AI domain
        $this->ruleChoice('ai_tools_used', 'ai', [
            ['chatgpt',          'ChatGPT',                         +10, 'USED_AI'],
            ['gemini',           'Gemini',                          +10, 'USED_AI'],
            ['ms_copilot',       'Microsoft Copilot',               +10, 'USED_AI'],
            ['ai_chatbot',       'AI Chatbot CSKH',                 +10, 'USED_AI'],
            ['ai_content',       'AI viết content',                 +8,  'USED_AI'],
            ['ai_data_analysis', 'AI phân tích dữ liệu',            +12, 'USED_AI'],
            ['ai_image',         'AI tạo hình ảnh',                 +5,  'USED_AI'],
            ['never_used',       'Chưa dùng AI',                   -5,  null],
        ]);
        $this->ruleChoice('ai_knowledge_level', 'ai', [
            ['no_knowledge', 'Không biết gì về AI',                -10, 'STAFF_NO_AI'],
            ['basic',        'Biết cơ bản',                        0,   null],
            ['tried',        'Đã thử nghiệm',                      +5,  null],
            ['used_at_work', 'Dùng trong công việc',               +12, null],
            ['proficient',   'Thành thạo',                         +20, null],
        ]);
        $this->ruleChoice('ai_concerns', 'ai', [
            ['dont_know_where_to_start', 'Không biết bắt đầu từ đâu', -12, null],
            ['staff_cant_use',           'Nhân viên không biết dùng',  -8,  null],
        ]);
        $this->ruleChoice('ai_readiness_level', 'ai', [
            ['explore_only',   'Chỉ tìm hiểu',                     0,   null],
            ['small_pilot',    'Thử nghiệm nhỏ',                   +8,  null],
            ['partial_deploy', 'Triển khai một phần',               +15, null],
            ['full_deploy',    'Triển khai toàn diện',              +20, 'AI_INVESTMENT_READY'],
        ]);
        $this->ruleChoice('digital_budget', 'ai', [
            ['no_budget',  'Chưa có ngân sách',                    -8,  null],
            ['under_50m',  'Dưới 50 triệu/năm',                   +5,  null],
            ['50_200m',    '50 - 200 triệu/năm',                  +10, null],
            ['200_500m',   '200 - 500 triệu/năm',                 +15, null],
            ['over_500m',  'Trên 500 triệu/năm',                  +20, 'AI_INVESTMENT_READY'],
        ]);
    }

    private function ruleChoice(string $fieldKey, string $domainCode, array $options): void
    {
        $rule = ScoreRule::create([
            'assessment_code'      => self::CODE,
            'field_key'            => $fieldKey,
            'feature_code'         => $fieldKey,
            'domain_code'          => $domainCode,
            'signal_flag'          => null,
            'score_if_true'        => 0,
            'score_if_false'       => 0,
            'condition_type'       => 'multi_choice',
            'question_scoring_type' => 'multi_choice',
            'is_active'            => true,
        ]);

        foreach ($options as $i => [$optionValue, $optionLabel, $score, $flag]) {
            ScoreRuleOption::create([
                'rule_id'      => $rule->id,
                'option_value' => $optionValue,
                'option_label' => $optionLabel,
                'score'        => $score,
                'signal_flag'  => $flag,
                'sort_order'   => $i,
            ]);
        }
    }

    // ── Pain Point Rules ──────────────────────────────────────────────────────

    private function seedPainPointRules(): void
    {
        $rules = [
            ['pain_point_code' => 'sales_leakage',        'label' => 'Rò rỉ lead bán hàng',              'required_flags' => 'LEAD_LOSS,!HAS_CRM'],
            ['pain_point_code' => 'fragmented_data',       'label' => 'Dữ liệu phân tán',                 'required_flags' => 'DATA_FRAGMENTED'],
            ['pain_point_code' => 'manual_workflow',       'label' => 'Thiếu quy trình chuẩn (SOP)',      'required_flags' => '!HAS_SOP'],
            ['pain_point_code' => 'lack_of_visibility',    'label' => 'CEO thiếu tầm nhìn vận hành',      'required_flags' => 'CEO_HARD_TO_CONTROL'],
            ['pain_point_code' => 'training_dependency',   'label' => 'Phụ thuộc nhân sự đào tạo',        'required_flags' => 'TRAINING_DEPENDENCY'],
        ];

        foreach ($rules as $r) {
            PainPointRule::create(array_merge(['assessment_code' => self::CODE, 'is_active' => true], $r));
        }
    }

    // ── Recommendation Rules ──────────────────────────────────────────────────

    private function seedRecommendationRules(): void
    {
        $rules = [
            [
                'recommendation_code' => 'workflow_foundation',
                'label'               => 'Xây dựng nền tảng quy trình',
                'description'         => 'Chuẩn hóa SOP, xây dựng workflow phê duyệt, thiết lập dashboard theo dõi tiến độ.',
                'trigger_domain'      => 'workflow',
                'threshold_score'     => 40.00,
                'priority'            => 1,
            ],
            [
                'recommendation_code' => 'crm_setup',
                'label'               => 'Thiết lập hệ thống CRM',
                'description'         => 'Triển khai CRM để quản lý lead, tự động follow-up và đo lường hiệu quả sale.',
                'trigger_domain'      => 'sales',
                'threshold_score'     => 50.00,
                'priority'            => 1,
            ],
            [
                'recommendation_code' => 'hr_process_setup',
                'label'               => 'Chuẩn hóa quy trình HR',
                'description'         => 'Xây dựng checklist onboarding, KPI nhân sự, tài liệu đào tạo chuẩn để giảm phụ thuộc con người.',
                'trigger_domain'      => 'hr',
                'threshold_score'     => 40.00,
                'priority'            => 2,
            ],
            [
                'recommendation_code' => 'data_cleanup',
                'label'               => 'Tập trung & làm sạch dữ liệu',
                'description'         => 'Thống nhất nguồn dữ liệu, thiết lập backup định kỳ, phân quyền truy cập.',
                'trigger_domain'      => 'data',
                'threshold_score'     => 40.00,
                'priority'            => 2,
            ],
            [
                'recommendation_code' => 'ai_training',
                'label'               => 'Đào tạo nhận thức AI cho đội ngũ',
                'description'         => 'Tổ chức workshop AI cơ bản, xác định use case AI phù hợp với ngành nghề cụ thể.',
                'trigger_domain'      => 'ai',
                'threshold_score'     => 30.00,
                'priority'            => 2,
            ],
        ];

        foreach ($rules as $r) {
            RecommendationRule::create(array_merge(['assessment_code' => self::CODE, 'is_active' => true], $r));
        }
    }

    // ── Roadmap ───────────────────────────────────────────────────────────────

    private function seedRoadmap(): void
    {
        $this->seedRoadmapForBand('MANUAL_OPERATION', [
            [
                'phase_code' => 'digitalize_basics', 'title' => 'Giai đoạn 1: Số hóa cơ bản',
                'description' => 'Thay thế sổ sách giấy và Excel bằng công cụ số.', 'duration_weeks' => 4, 'sort_order' => 1,
                'milestones' => ['Chuyển toàn bộ dữ liệu khách hàng lên Google Sheet / CRM cơ bản', 'Thiết lập Zalo / Slack cho giao tiếp nội bộ', 'Tạo file theo dõi công việc hàng ngày cho từng phòng ban'],
            ],
            [
                'phase_code' => 'build_sop', 'title' => 'Giai đoạn 2: Xây dựng SOP',
                'description' => 'Chuẩn hóa quy trình vận hành cốt lõi.', 'duration_weeks' => 6, 'sort_order' => 2,
                'milestones' => ['Viết SOP cho quy trình sales (tiếp nhận lead → chốt đơn)', 'Viết SOP onboarding nhân sự mới', 'Xây dựng checklist công việc hàng ngày / tuần'],
            ],
        ]);

        $this->seedRoadmapForBand('DIGITAL_FOUNDATION', [
            [
                'phase_code' => 'integrate_tools', 'title' => 'Giai đoạn 1: Tích hợp công cụ',
                'description' => 'Kết nối các công cụ số đang dùng.', 'duration_weeks' => 4, 'sort_order' => 1,
                'milestones' => ['Triển khai CRM tích hợp với kênh tiếp thị', 'Thiết lập dashboard CEO theo dõi KPI realtime', 'Đồng bộ dữ liệu giữa các phòng ban'],
            ],
            [
                'phase_code' => 'automate_workflow', 'title' => 'Giai đoạn 2: Tự động hóa quy trình',
                'description' => 'Áp dụng automation cho các tác vụ lặp lại.', 'duration_weeks' => 6, 'sort_order' => 2,
                'milestones' => ['Tự động gửi email/Zalo follow-up khách hàng', 'Tự động tạo báo cáo định kỳ', 'Workflow phê duyệt nội bộ tự động'],
            ],
            [
                'phase_code' => 'ai_pilot', 'title' => 'Giai đoạn 3: Thử nghiệm AI',
                'description' => 'Áp dụng AI vào một nghiệp vụ cụ thể.', 'duration_weeks' => 8, 'sort_order' => 3,
                'milestones' => ['Triển khai AI chatbot CSKH cơ bản', 'Dùng AI phân tích dữ liệu sales', 'Đào tạo nhân sự dùng AI công cụ hàng ngày'],
            ],
        ]);

        $this->seedRoadmapForBand('AI_READY', [
            [
                'phase_code' => 'ai_deploy_core', 'title' => 'Giai đoạn 1: Triển khai AI cốt lõi',
                'description' => 'Áp dụng AI vào các nghiệp vụ mang lại ROI cao nhất.', 'duration_weeks' => 6, 'sort_order' => 1,
                'milestones' => ['AI phân tích lead và dự đoán khả năng chốt đơn', 'AI gợi ý nội dung training cho nhân sự mới', 'AI tự động phân loại và ưu tiên ticket CSKH'],
            ],
            [
                'phase_code' => 'ai_expand', 'title' => 'Giai đoạn 2: Mở rộng AI',
                'description' => 'Nhân rộng AI sang các phòng ban còn lại.', 'duration_weeks' => 8, 'sort_order' => 2,
                'milestones' => ['AI dự báo doanh thu và tồn kho', 'AI hỗ trợ tuyển dụng và đánh giá nhân sự', 'CEO AI dashboard tổng hợp từ tất cả phòng ban'],
            ],
        ]);

        $this->seedRoadmapForBand('AI_TRANSFORMATION', [
            [
                'phase_code' => 'ai_culture', 'title' => 'Giai đoạn 1: Văn hóa AI',
                'description' => 'Xây dựng văn hóa dữ liệu và AI trong toàn tổ chức.', 'duration_weeks' => 4, 'sort_order' => 1,
                'milestones' => ['Thiết lập AI Center of Excellence nội bộ', 'Chương trình AI Champion cho từng phòng ban', 'KPI gắn liền với việc ứng dụng AI hiệu quả'],
            ],
            [
                'phase_code' => 'ai_innovation', 'title' => 'Giai đoạn 2: Đổi mới sáng tạo với AI',
                'description' => 'Phát triển sản phẩm/dịch vụ mới dựa trên AI.', 'duration_weeks' => 12, 'sort_order' => 2,
                'milestones' => ['Xây dựng mô hình dự đoán nhu cầu thị trường', 'Phát triển sản phẩm AI-powered cho khách hàng', 'Kết nối hệ sinh thái đối tác công nghệ AI'],
            ],
        ]);
    }

    private function seedRoadmapForBand(string $bandCode, array $phases): void
    {
        foreach ($phases as $p) {
            $milestones = $p['milestones'];
            unset($p['milestones']);

            $phase = RoadmapPhase::create(array_merge([
                'assessment_code' => self::CODE,
                'maturity_level'  => $bandCode,
                'band_code'       => $bandCode,
            ], $p));

            foreach ($milestones as $i => $title) {
                RoadmapMilestone::create([
                    'phase_id'   => $phase->id,
                    'title'      => $title,
                    'sort_order' => $i,
                ]);
            }
        }
    }
}
