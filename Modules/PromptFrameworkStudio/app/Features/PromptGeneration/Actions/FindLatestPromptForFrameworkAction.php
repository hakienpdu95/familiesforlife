<?php

namespace Modules\PromptFrameworkStudio\Features\PromptGeneration\Actions;

use Lorisleiva\Actions\Concerns\AsAction;
use Modules\PromptFrameworkStudio\Models\GeneratedPrompt;

/**
 * spec/AIIdeaMatrixGenerator.md §3 — "Dùng lại giá trị từ prompt trước": thay cho khái niệm
 * Campaign (bảng DB riêng) mà `PromptFrameworkStudio` chưa có, tìm `GeneratedPrompt` GẦN NHẤT cùng
 * `framework_key` để prefill `field_values` khi tạo prompt mới — biên tập viên không phải gõ lại
 * field campaign-level (VD `red_thread`/`audience` ở `heritage_idea_matrix`) mỗi lần.
 *
 * KHÔNG owner-based — trả về bản ghi gần nhất của BẤT KỲ ai (cùng nguyên tắc "không owner-based ACL"
 * §5/§2.1 của module: mọi người có quyền xem/sửa MỌI prompt).
 */
class FindLatestPromptForFrameworkAction
{
    use AsAction;

    public function handle(string $frameworkKey): ?GeneratedPrompt
    {
        return GeneratedPrompt::where('framework_key', $frameworkKey)
            ->latest('id')
            ->first();
    }
}
