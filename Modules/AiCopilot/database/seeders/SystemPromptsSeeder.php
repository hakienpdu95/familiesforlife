<?php

namespace Modules\AiCopilot\Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Str;
use Modules\AiCopilot\Models\AiAgent;
use Modules\AiCopilot\Models\AiPrompt;

class SystemPromptsSeeder extends Seeder
{
    private array $prompts = [
        'sop.step_draft' => [
            'name'          => 'Default SOP Step Draft',
            'system_prompt' => 'Bạn là chuyên gia thiết kế quy trình vận hành (SOP). Hãy soạn thảo nội dung bước quy trình rõ ràng, chi tiết, dễ thực hiện. Viết bằng tiếng Việt, ngôn ngữ chuyên nghiệp, có cấu trúc.',
            'user_template' => "Hãy soạn thảo nội dung cho bước SOP sau:\n\nTiêu đề bước: {{step_title}}\nPhòng ban: {{department}}\nMô tả thêm: {{user_note}}\n\nYêu cầu output:\n- Mục đích của bước\n- Người thực hiện\n- Các bước chi tiết (danh sách đánh số)\n- Tiêu chí hoàn thành",
            'variables_schema' => [
                ['key' => 'step_title',  'type' => 'string', 'required' => true],
                ['key' => 'department',  'type' => 'string', 'required' => true],
                ['key' => 'user_note',   'type' => 'string', 'required' => false],
            ],
        ],
        'sop.summarize' => [
            'name'          => 'Default SOP Summary',
            'system_prompt' => 'Bạn là chuyên gia tóm tắt tài liệu. Hãy tóm tắt quy trình SOP một cách súc tích, giữ lại các điểm chính.',
            'user_template' => "Tóm tắt quy trình SOP sau:\n\nTên quy trình: {{sop_title}}\nNội dung: {{sop_content}}\n\nOutput: Tóm tắt trong 3-5 câu, nêu mục đích, đối tượng thực hiện và các bước chính.",
            'variables_schema' => [
                ['key' => 'sop_title',   'type' => 'string', 'required' => true],
                ['key' => 'sop_content', 'type' => 'string', 'required' => true],
            ],
        ],
        'kpi.analysis' => [
            'name'          => 'Default KPI Analysis',
            'system_prompt' => 'Bạn là chuyên gia phân tích hiệu suất kinh doanh. Hãy phân tích dữ liệu KPI và đưa ra nhận xét, khuyến nghị hành động cụ thể.',
            'user_template' => "Phân tích KPI sau và đưa ra khuyến nghị:\n\nNhân viên: {{employee_name}}\nKỳ: {{cycle_label}}\nMục tiêu: {{kpi_title}}\nGiá trị mục tiêu: {{target_value}} {{unit}}\nGiá trị hiện tại: {{current_value}} {{unit}}\nMức độ hoàn thành: {{achievement_pct}}%\n\nOutput: Nhận xét ngắn (2-3 câu) + 2-3 hành động cải thiện cụ thể.",
            'variables_schema' => [
                ['key' => 'employee_name',   'type' => 'string', 'required' => true],
                ['key' => 'cycle_label',     'type' => 'string', 'required' => true],
                ['key' => 'kpi_title',       'type' => 'string', 'required' => true],
                ['key' => 'target_value',    'type' => 'string', 'required' => true],
                ['key' => 'current_value',   'type' => 'string', 'required' => true],
                ['key' => 'achievement_pct', 'type' => 'string', 'required' => true],
                ['key' => 'unit',            'type' => 'string', 'required' => false],
            ],
        ],
        'hr.feedback_draft' => [
            'name'          => 'Default HR Feedback',
            'system_prompt' => 'Bạn là chuyên gia HR với kinh nghiệm viết nhận xét đánh giá nhân viên. Hãy viết nhận xét chuyên nghiệp, khách quan, mang tính xây dựng.',
            'user_template' => "Soạn thảo nhận xét đánh giá nhân viên:\n\nNhân viên: {{employee_name}}\nVị trí: {{position}}\nKỳ đánh giá: {{period}}\nĐiểm mạnh: {{strengths}}\nCần cải thiện: {{improvements}}\nĐiểm KPI: {{kpi_score}}/100\n\nOutput: Nhận xét 150-200 từ, bao gồm điểm mạnh, điểm cần phát triển và kế hoạch tiếp theo.",
            'variables_schema' => [
                ['key' => 'employee_name', 'type' => 'string', 'required' => true],
                ['key' => 'position',      'type' => 'string', 'required' => true],
                ['key' => 'period',        'type' => 'string', 'required' => true],
                ['key' => 'strengths',     'type' => 'string', 'required' => false],
                ['key' => 'improvements',  'type' => 'string', 'required' => false],
                ['key' => 'kpi_score',     'type' => 'string', 'required' => false],
            ],
        ],
        'hr.job_description' => [
            'name'          => 'Default JD Writer',
            'system_prompt' => 'Bạn là chuyên gia HR viết mô tả công việc (JD) hấp dẫn, rõ ràng, thu hút ứng viên phù hợp.',
            'user_template' => "Viết JD cho vị trí sau:\n\nVị trí: {{job_title}}\nPhòng ban: {{department}}\nYêu cầu chính: {{requirements}}\nPhúc lợi nổi bật: {{benefits}}\n\nOutput: JD đầy đủ bao gồm: Mô tả công việc, Trách nhiệm chính (5-7 bullet), Yêu cầu (must-have + nice-to-have), Quyền lợi.",
            'variables_schema' => [
                ['key' => 'job_title',    'type' => 'string', 'required' => true],
                ['key' => 'department',   'type' => 'string', 'required' => true],
                ['key' => 'requirements', 'type' => 'string', 'required' => false],
                ['key' => 'benefits',     'type' => 'string', 'required' => false],
            ],
        ],
        'lead.score_analysis' => [
            'name'          => 'Default Lead Scorer',
            'system_prompt' => 'Bạn là chuyên gia sales phân tích tiềm năng khách hàng. Hãy đánh giá lead dựa trên thông tin cung cấp.',
            'user_template' => "Phân tích và đánh giá lead:\n\nTên: {{lead_name}}\nCông ty: {{company}}\nNguồn: {{source}}\nGhi chú: {{notes}}\n\nOutput: (1) Mức độ tiềm năng (Cao/Trung/Thấp) + lý do, (2) 2-3 bước tiếp cận phù hợp.",
            'variables_schema' => [
                ['key' => 'lead_name', 'type' => 'string', 'required' => true],
                ['key' => 'company',   'type' => 'string', 'required' => false],
                ['key' => 'source',    'type' => 'string', 'required' => false],
                ['key' => 'notes',     'type' => 'string', 'required' => false],
            ],
        ],
        'email.draft' => [
            'name'          => 'Default Email Drafter',
            'system_prompt' => 'Bạn là chuyên gia viết email doanh nghiệp. Hãy soạn email chuyên nghiệp, rõ ràng, phù hợp với ngữ cảnh.',
            'user_template' => "Soạn email doanh nghiệp:\n\nNgười nhận: {{recipient}}\nChủ đề: {{subject}}\nNội dung chính: {{key_points}}\nTone: {{tone}}\n\nOutput: Email hoàn chỉnh (tiêu đề + thân + lời kết).",
            'variables_schema' => [
                ['key' => 'recipient',   'type' => 'string', 'required' => true],
                ['key' => 'subject',     'type' => 'string', 'required' => true],
                ['key' => 'key_points',  'type' => 'string', 'required' => true],
                ['key' => 'tone',        'type' => 'string', 'required' => false],
            ],
        ],
        'general.summarize' => [
            'name'          => 'Default Text Summarizer',
            'system_prompt' => 'Bạn là chuyên gia tóm tắt nội dung. Hãy tóm tắt văn bản một cách súc tích, chính xác.',
            'user_template' => "Tóm tắt nội dung sau:\n\n{{content}}\n\nYêu cầu tóm tắt: {{instructions}}",
            'variables_schema' => [
                ['key' => 'content',      'type' => 'string', 'required' => true],
                ['key' => 'instructions', 'type' => 'string', 'required' => false],
            ],
        ],
        'general.translate' => [
            'name'          => 'Default Translator',
            'system_prompt' => 'Bạn là chuyên gia dịch thuật Việt-Anh và Anh-Việt. Hãy dịch chính xác, giữ nguyên ý nghĩa và tone của bản gốc.',
            'user_template' => "Dịch nội dung sau sang {{target_language}}:\n\n{{content}}",
            'variables_schema' => [
                ['key' => 'content',         'type' => 'string', 'required' => true],
                ['key' => 'target_language', 'type' => 'string', 'required' => true],
            ],
        ],
    ];

    public function run(): void
    {
        foreach ($this->prompts as $agentSlug => $data) {
            $agent = AiAgent::withoutTenant()
                ->withoutGlobalScope('active')
                ->where('slug', $agentSlug)
                ->whereNull('organization_id')
                ->first();

            if (!$agent) {
                $this->command->warn("  ! Agent [{$agentSlug}] not found, skipping prompt.");
                continue;
            }

            AiPrompt::withoutTenant()->updateOrCreate(
                ['agent_id' => $agent->id, 'organization_id' => null, 'is_default' => true],
                array_merge([
                    'uuid'       => Str::uuid()->toString(),
                    'is_active'  => true,
                    'version'    => 1,
                ], $data)
            );
        }

        $this->command->info('  ✓ Default prompts for 9 system agents seeded.');
    }
}
