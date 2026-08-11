<?php

namespace Modules\PromptFrameworkStudio\Features\FrameworkLibrary\Http;

use App\Http\Controllers\Controller;
use Illuminate\View\View;
use Modules\PromptFrameworkStudio\Features\PromptGeneration\Actions\RenderPromptFromFrameworkAction;

/**
 * spec/PromptFrameworkStudio_Technical_Specification.md §0/§1 — trang "học": đọc thẳng từ config,
 * không có state/DB nào ở đây. Tái dùng CHÍNH RenderPromptFromFrameworkAction (feature
 * PromptGeneration) để dựng sẵn "ví dụ prompt hoàn chỉnh" cho từng framework — tránh viết lại
 * logic ghép chuỗi lần 2 chỉ để hiển thị ví dụ.
 *
 * (2026-08-11) — gom theo `framework['group']` (mặc định 'Framework prompt engineering tổng quát'
 * khi vắng mặt, xem docblock config) để trang không còn là 1 lưới phẳng 23 thẻ lẫn lộn giữa 18
 * framework tổng quát (CO-STAR, RACE...) và 5 mẫu "Chiến lược nội dung" có nhiệm vụ cố định — 2
 * loại khác bản chất, gộp chung không nhãn sẽ khiến người không rành khó phân biệt "mẫu nào tự
 * điền nội dung, mẫu nào đã có sẵn việc cần làm".
 */
class FrameworkLibraryController extends Controller
{
    public function index(RenderPromptFromFrameworkAction $renderPrompt): View
    {
        $frameworks = config('prompt_framework_studio.frameworks');

        $groupedFrameworks = collect($frameworks)
            ->map(function (array $framework, string $key) use ($renderPrompt) {
                $framework['key'] = $key;
                $framework['rendered_example'] = $renderPrompt->handle($key, $framework['example']);

                return $framework;
            })
            ->groupBy(fn (array $framework) => $framework['group'] ?? 'Framework prompt engineering tổng quát')
            ->all();

        return view('promptframeworkstudio::library.index', [
            'groupedFrameworks' => $groupedFrameworks,
        ]);
    }
}
