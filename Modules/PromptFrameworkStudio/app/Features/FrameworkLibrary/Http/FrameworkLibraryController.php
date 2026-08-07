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
 */
class FrameworkLibraryController extends Controller
{
    public function index(RenderPromptFromFrameworkAction $renderPrompt): View
    {
        $frameworks = config('prompt_framework_studio.frameworks');

        $frameworksWithRenderedExample = collect($frameworks)
            ->map(function (array $framework, string $key) use ($renderPrompt) {
                $framework['rendered_example'] = $renderPrompt->handle($key, $framework['example']);

                return $framework;
            })
            ->all();

        return view('promptframeworkstudio::library.index', [
            'frameworks' => $frameworksWithRenderedExample,
        ]);
    }
}
