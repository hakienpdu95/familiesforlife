<?php

namespace Modules\PromptFrameworkStudio\Features\PromptGeneration\Http;

use App\Http\Controllers\Controller;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;
use Modules\ContentFoundation\Actions\ListCategoryFoundationsAction;
use Modules\Post\Models\PostCategory;
use Modules\PromptFrameworkStudio\Features\PromptGeneration\Actions\CreateGeneratedPromptAction;
use Modules\PromptFrameworkStudio\Features\PromptGeneration\Actions\RegenerateGeneratedPromptAction;
use Modules\PromptFrameworkStudio\Features\PromptGeneration\Http\Requests\StoreGeneratedPromptRequest;
use Modules\PromptFrameworkStudio\Features\PromptGeneration\Http\Requests\UpdateGeneratedPromptRequest;
use Modules\PromptFrameworkStudio\Models\GeneratedPrompt;

/**
 * spec/PromptFrameworkStudio_Technical_Specification.md §5/§6 — gate phẳng bằng middleware
 * 'can:prompt_framework_studio.use' ở routes/web.php, KHÔNG Policy riêng theo model (không có
 * owner-based ACL — mọi người có permission xem/sửa/xoá được MỌI prompt, cùng ContentOutlines).
 */
class PromptGenerationController extends Controller
{
    public function index(): View
    {
        return view('promptframeworkstudio::prompts.index');
    }

    /**
     * §7 (v1.3 — UI/UX) — nhận sẵn `?framework=key` khi đến từ nút "Dùng framework này" ở trang
     * Thư viện, để người dùng không rành kỹ thuật không phải chọn lại framework 2 lần. Validate
     * chặt theo config keys — key lạ/không hợp lệ thì bỏ qua (về trạng thái chưa chọn gì, KHÔNG
     * lỗi 404/500 vì đây chỉ là gợi ý tiện lợi, không phải tham số bắt buộc).
     */
    public function create(Request $request, ListCategoryFoundationsAction $listCategoryFoundations): View
    {
        $frameworks = config('prompt_framework_studio.frameworks');
        $preselectedKey = $request->query('framework');
        $preselectedKey = (is_string($preselectedKey) && array_key_exists($preselectedKey, $frameworks))
            ? $preselectedKey
            : null;

        return view('promptframeworkstudio::prompts.create', [
            'frameworks' => $frameworks,
            'preselectedKey' => $preselectedKey,
            // §4.4 (v2.7) — `withFoundationDetails: false` = chỉ nạp bản rút gọn đủ dựng picker;
            // nội dung ngữ cảnh đầy đủ lấy sau qua editorialContext() khi người dùng chọn 1 mục
            // cụ thể (cùng cách CoreIdeaExtractor tránh nhồi chi tiết của ~50 chuyên mục vào HTML).
            'categories' => $listCategoryFoundations->handle(withFoundationDetails: false),
        ]);
    }

    public function store(StoreGeneratedPromptRequest $request, CreateGeneratedPromptAction $action): RedirectResponse
    {
        $prompt = $action->handle(
            frameworkKey: $request->validated('framework_key'),
            label: $request->validated('label'),
            fieldValues: $request->validated('field_values'),
            createdBy: $request->user()->id,
            postCategoryId: $this->resolveCategoryId($request->validated('post_category_uuid')),
        );

        return redirect()->route('backend.promptstudio.prompts.show', $prompt)
            ->with('success', 'Đã sinh prompt — sao chép bên dưới để dùng.');
    }

    public function show(GeneratedPrompt $prompt): View
    {
        $prompt->load('category');

        return view('promptframeworkstudio::prompts.show', [
            'prompt' => $prompt,
            'framework' => $prompt->framework(), // null = orphaned (§5.4)
        ]);
    }

    /**
     * §5.4 — orphaned (framework đã bị gỡ khỏi config) chuyển hướng sang trang xem (read-only),
     * KHÔNG render form sửa (không còn fields/template để dựng lại form).
     */
    public function edit(GeneratedPrompt $prompt, ListCategoryFoundationsAction $listCategoryFoundations): View|RedirectResponse
    {
        $framework = $prompt->framework();

        if (! $framework) {
            return redirect()->route('backend.promptstudio.prompts.show', $prompt)
                ->with('error', "Framework \"{$prompt->framework_key}\" đã bị gỡ khỏi hệ thống — không thể sửa hoặc sinh lại. Bạn vẫn có thể xem và sao chép nội dung đã lưu, hoặc xoá bản ghi này.");
        }

        $prompt->load('category');

        return view('promptframeworkstudio::prompts.edit', [
            'prompt' => $prompt,
            'framework' => $framework,
            'categories' => $listCategoryFoundations->handle(withFoundationDetails: false),
        ]);
    }

    public function update(UpdateGeneratedPromptRequest $request, GeneratedPrompt $prompt, RegenerateGeneratedPromptAction $action): RedirectResponse
    {
        $action->handle(
            prompt: $prompt,
            label: $request->validated('label'),
            fieldValues: $request->validated('field_values'),
            updatedBy: $request->user()->id,
            postCategoryId: $this->resolveCategoryId($request->validated('post_category_uuid')),
        );

        return redirect()->route('backend.promptstudio.prompts.show', $prompt)
            ->with('success', 'Đã sinh lại prompt.');
    }

    public function destroy(GeneratedPrompt $prompt): RedirectResponse
    {
        // §5.4 — destroy hoạt động bình thường kể cả khi orphaned, không cản việc dọn dữ liệu cũ.
        $prompt->delete();

        return redirect()->route('backend.promptstudio.prompts.index')->with('success', 'Đã xoá prompt.');
    }

    /**
     * §4.4 (v2.7) — form nhận `uuid` (route key của PostCategory, không lộ id tự tăng ra HTML),
     * Action nhận `id`. Đã `exists:post_categories,uuid` ở FormRequest nên tới đây uuid chắc chắn
     * hợp lệ; `value()` trả null khi không chọn gì.
     */
    private function resolveCategoryId(?string $uuid): ?int
    {
        return $uuid ? PostCategory::where('uuid', $uuid)->value('id') : null;
    }
}
