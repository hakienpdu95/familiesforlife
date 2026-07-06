<?php

namespace Modules\Assessment\Database\Seeders;

use Illuminate\Database\Seeder;
use Modules\Assessment\Models\SandboxEnvironment;
use Modules\Assessment\Models\SandboxTask;

class SandboxEnvironmentSeeder extends Seeder
{
    public function run(): void
    {
        if (SandboxEnvironment::where('organization_id', null)->exists()) {
            $this->command->info('SandboxEnvironments (global) đã tồn tại, bỏ qua.');
            return;
        }

        $envs = [
            [
                'env_code'    => 'AI_OFFICE_F1',
                'name'        => 'AI Office — Foundation',
                'type'        => 'office',
                'tier'        => 1,
                'description' => 'Thực hành AI cho công việc văn phòng và hành chính cơ bản.',
                'tasks'       => [
                    [
                        'title'               => 'Soạn email chuyên nghiệp bằng AI',
                        'instruction'         => 'Dùng AI để soạn 3 email: (1) xin lỗi khách hàng về sự chậm trễ, (2) nhắc nhở deadline nội bộ, (3) báo cáo tóm tắt tuần cho quản lý.',
                        'expected_output'     => '3 email hoàn chỉnh, tông phù hợp với từng ngữ cảnh, không lỗi chính tả.',
                        'scoring_rubric'      => 'Tông phù hợp ngữ cảnh|Cấu trúc rõ ràng|Không lỗi chính tả|Dùng AI hiệu quả',
                        'time_limit_minutes'  => 20,
                        'ai_tools_allowed'    => 'ChatGPT|Claude|Gemini',
                        'target_position_code'=> null,
                        'sort_order'          => 1,
                    ],
                    [
                        'title'               => 'Tạo bảng tổng hợp dữ liệu bằng AI',
                        'instruction'         => 'Nhập dữ liệu mẫu (10 dòng) và dùng AI để tạo bảng tổng hợp, phân tích xu hướng, và gợi ý hành động.',
                        'expected_output'     => 'Bảng tổng hợp đúng, có 3 nhận xét phân tích, 2 gợi ý hành động cụ thể.',
                        'scoring_rubric'      => 'Độ chính xác bảng số liệu|Chất lượng phân tích|Gợi ý khả thi',
                        'time_limit_minutes'  => 25,
                        'ai_tools_allowed'    => 'ChatGPT|Claude|Gemini|Excel',
                        'target_position_code'=> 'B2_HR',
                        'sort_order'          => 2,
                    ],
                ],
            ],
            [
                'env_code'    => 'AI_DATA_F1',
                'name'        => 'AI Data — Foundation',
                'type'        => 'data',
                'tier'        => 1,
                'description' => 'Thực hành nhập liệu, làm sạch và phân tích dữ liệu cơ bản với AI.',
                'tasks'       => [
                    [
                        'title'               => 'Làm sạch và chuẩn hóa bộ dữ liệu khách hàng',
                        'instruction'         => 'Cho trước bộ dữ liệu 50 khách hàng có lỗi (thiếu trường, sai định dạng, trùng lặp). Dùng AI để phát hiện và đề xuất cách sửa.',
                        'expected_output'     => 'Danh sách lỗi được phát hiện, phương án xử lý từng lỗi, bộ dữ liệu sau khi làm sạch.',
                        'scoring_rubric'      => 'Phát hiện đủ lỗi|Phương án xử lý hợp lý|Dữ liệu sạch sau cùng',
                        'time_limit_minutes'  => 30,
                        'ai_tools_allowed'    => 'ChatGPT|Claude|Gemini',
                        'target_position_code'=> 'B4_OPS',
                        'sort_order'          => 1,
                    ],
                ],
            ],
            [
                'env_code'    => 'AI_SALES_F1',
                'name'        => 'AI Sales — Foundation',
                'type'        => 'sales',
                'tier'        => 1,
                'description' => 'Thực hành ứng dụng AI trong quy trình bán hàng và chăm sóc khách hàng.',
                'tasks'       => [
                    [
                        'title'               => 'Phân tích profile khách hàng và đề xuất chiến lược tiếp cận',
                        'instruction'         => 'Cho trước thông tin 5 lead. Dùng AI để phân tích từng lead và đề xuất cách tiếp cận phù hợp.',
                        'expected_output'     => '5 phân tích lead với chiến lược tiếp cận cụ thể, kênh liên lạc phù hợp, thông điệp mẫu.',
                        'scoring_rubric'      => 'Độ sâu phân tích|Chiến lược phù hợp profile|Thông điệp cá nhân hóa',
                        'time_limit_minutes'  => 25,
                        'ai_tools_allowed'    => 'ChatGPT|Claude|Gemini',
                        'target_position_code'=> 'B1_SALES',
                        'sort_order'          => 1,
                    ],
                ],
            ],
            [
                'env_code'    => 'AI_HR_F1',
                'name'        => 'AI HR — Foundation',
                'type'        => 'hr',
                'tier'        => 1,
                'description' => 'Thực hành ứng dụng AI trong tuyển dụng và quản lý nhân sự.',
                'tasks'       => [
                    [
                        'title'               => 'Viết JD và bộ câu hỏi phỏng vấn bằng AI',
                        'instruction'         => 'Cho trước thông tin vị trí Marketing Executive. Dùng AI để viết JD đầy đủ và 10 câu hỏi phỏng vấn theo competency model.',
                        'expected_output'     => 'JD hoàn chỉnh (overview, requirements, benefits) + 10 câu hỏi phỏng vấn có rubric chấm điểm.',
                        'scoring_rubric'      => 'JD rõ ràng hấp dẫn|Câu hỏi đánh giá đúng competency|Rubric khả thi',
                        'time_limit_minutes'  => 30,
                        'ai_tools_allowed'    => 'ChatGPT|Claude|Gemini',
                        'target_position_code'=> 'B2_HR',
                        'sort_order'          => 1,
                    ],
                ],
            ],
            [
                'env_code'    => 'AI_WORKFLOW_F1',
                'name'        => 'AI Workflow — Foundation',
                'type'        => 'workflow',
                'tier'        => 1,
                'description' => 'Thực hành thiết kế và tối ưu quy trình với AI.',
                'tasks'       => [
                    [
                        'title'               => 'Thiết kế SOP quy trình onboarding nhân viên mới bằng AI',
                        'instruction'         => 'Dùng AI để thiết kế SOP onboarding hoàn chỉnh gồm: checklist ngày 1, tuần 1, tháng đầu tiên. Xác định owner cho từng bước.',
                        'expected_output'     => 'SOP 3 giai đoạn với checklist chi tiết, owner rõ ràng, timeline hợp lý.',
                        'scoring_rubric'      => 'Đầy đủ 3 giai đoạn|Checklist chi tiết khả thi|Owner phù hợp vai trò',
                        'time_limit_minutes'  => 35,
                        'ai_tools_allowed'    => 'ChatGPT|Claude|Gemini',
                        'target_position_code'=> 'B4_OPS',
                        'sort_order'          => 1,
                    ],
                ],
            ],
            [
                'env_code'    => 'AI_LEADERSHIP_F1',
                'name'        => 'AI Leadership — Foundation',
                'type'        => 'leadership',
                'tier'        => 1,
                'description' => 'Thực hành ra quyết định chiến lược và lãnh đạo với hỗ trợ AI.',
                'tasks'       => [
                    [
                        'title'               => 'Phân tích báo cáo kinh doanh và đề xuất chiến lược AI',
                        'instruction'         => 'Cho trước báo cáo Q3 của một doanh nghiệp SME. Dùng AI để phân tích điểm yếu, cơ hội, và đề xuất 3 ứng dụng AI có ROI cao nhất.',
                        'expected_output'     => 'Phân tích SWOT có AI, 3 use case AI với ROI estimate và implementation roadmap 3 tháng.',
                        'scoring_rubric'      => 'Phân tích sâu điểm yếu|Use case AI thực tế|ROI có căn cứ|Roadmap khả thi',
                        'time_limit_minutes'  => 40,
                        'ai_tools_allowed'    => 'ChatGPT|Claude|Gemini',
                        'target_position_code'=> 'B6_LEADERSHIP',
                        'sort_order'          => 1,
                    ],
                ],
            ],
        ];

        foreach ($envs as $envData) {
            $tasks = $envData['tasks'];
            unset($envData['tasks']);

            $env = SandboxEnvironment::create(array_merge(
                ['organization_id' => null, 'is_active' => true, 'sort_order' => 0],
                $envData
            ));

            foreach ($tasks as $t) {
                SandboxTask::create(array_merge(
                    ['sandbox_env_id' => $env->id, 'is_active' => true, 'uuid' => (string) \Illuminate\Support\Str::uuid()],
                    $t
                ));
            }

            $this->command->info("Created sandbox: {$env->env_code} with " . count($tasks) . " tasks");
        }
    }
}
