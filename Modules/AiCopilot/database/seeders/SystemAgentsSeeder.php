<?php

namespace Modules\AiCopilot\Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Str;
use Modules\AiCopilot\Models\AiAgent;

class SystemAgentsSeeder extends Seeder
{
    private array $agents = [
        [
            'slug'      => 'sop.step_draft',
            'name'      => 'SOP Step Drafter',
            'task_type' => 'sop',
            'model'     => 'claude-sonnet-4-6',
            'max_tokens'=> 2048,
            'description' => 'Draft nội dung chi tiết cho một bước quy trình SOP.',
        ],
        [
            'slug'      => 'sop.summarize',
            'name'      => 'SOP Summarizer',
            'task_type' => 'sop',
            'model'     => 'claude-haiku-4-5-20251001',
            'description' => 'Tóm tắt toàn bộ nội dung quy trình SOP.',
        ],
        [
            'slug'      => 'kpi.analysis',
            'name'      => 'KPI Analyst',
            'task_type' => 'kpi',
            'model'     => 'claude-sonnet-4-6',
            'max_tokens'=> 2048,
            'description' => 'Phân tích chỉ số KPI và gợi ý hành động cải thiện.',
        ],
        [
            'slug'      => 'hr.feedback_draft',
            'name'      => 'HR Feedback Drafter',
            'task_type' => 'hr',
            'model'     => 'claude-sonnet-4-6',
            'max_tokens'=> 2048,
            'description' => 'Draft nhận xét đánh giá nhân viên chuyên nghiệp.',
        ],
        [
            'slug'      => 'hr.job_description',
            'name'      => 'JD Writer',
            'task_type' => 'hr',
            'model'     => 'claude-sonnet-4-6',
            'max_tokens'=> 3000,
            'description' => 'Viết mô tả công việc từ tiêu chí tuyển dụng.',
        ],
        [
            'slug'      => 'lead.score_analysis',
            'name'      => 'Lead Scorer',
            'task_type' => 'lead',
            'model'     => 'claude-haiku-4-5-20251001',
            'description' => 'Phân tích thông tin lead và đề xuất mức độ ưu tiên.',
        ],
        [
            'slug'      => 'email.draft',
            'name'      => 'Email Drafter',
            'task_type' => 'email',
            'model'     => 'claude-haiku-4-5-20251001',
            'description' => 'Soạn thảo email chuyên nghiệp theo ngữ cảnh.',
        ],
        [
            'slug'      => 'general.summarize',
            'name'      => 'Text Summarizer',
            'task_type' => 'general',
            'model'     => 'claude-haiku-4-5-20251001',
            'description' => 'Tóm tắt văn bản dài thành các điểm chính.',
        ],
        [
            'slug'      => 'general.translate',
            'name'      => 'Translator',
            'task_type' => 'general',
            'model'     => 'claude-haiku-4-5-20251001',
            'description' => 'Dịch thuật văn bản đa ngôn ngữ (Việt-Anh và ngược lại).',
        ],
    ];

    public function run(): void
    {
        foreach ($this->agents as $data) {
            AiAgent::withoutTenant()
                ->withoutGlobalScope('active')
                ->updateOrCreate(
                    ['slug' => $data['slug'], 'organization_id' => null],
                    array_merge([
                        'uuid'        => Str::uuid()->toString(),
                        'temperature' => 0.70,
                        'max_tokens'  => 1024,
                        'timeout_seconds' => 30,
                        'sync_mode'   => false,
                        'is_active'   => true,
                        'is_system'   => true,
                    ], $data)
                );
        }

        $this->command->info('  ✓ 9 system AI agents seeded.');
    }
}
