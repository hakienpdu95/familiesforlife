<?php

namespace Modules\BulkIdeationPromptStudio\Features\Ideation\Http;

use App\Http\Controllers\Controller;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;
use Modules\BulkIdeationPromptStudio\Features\Ideation\Actions\CreateBulkIdeationPromptAction;
use Modules\BulkIdeationPromptStudio\Models\BulkIdeationPrompt;
use Modules\ContentFoundation\Actions\ListCategoryFoundationsAction;
use Modules\Post\Models\PostCategory;

/**
 * KHÔNG gọi AI Provider trong app — cùng nguyên tắc
 * Modules\VideoSeriesPromptStudio\Features\SeriesArchitecture\Http\SeriesArchitectureController:
 * chỉ ghép prompt + lưu lại, người dùng tự copy sang ChatGPT/Claude.
 */
class IdeationController extends Controller
{
    public function index(Request $request): View
    {
        $search = trim((string) $request->query('search', ''));

        $promptList = BulkIdeationPrompt::query()
            ->with('category')
            // tận dụng index('label') — tìm chuỗi con theo tên người dùng tự đặt (cùng
            // ListGeneratedPromptsForAdminHandler của PromptFrameworkStudio).
            ->when($search !== '', fn ($q) => $q->where('label', 'like', '%'.$search.'%'))
            ->latest()
            ->paginate(20)
            ->withQueryString();

        return view('bulkideationpromptstudio::index', [
            'promptList' => $promptList,
            'search' => $search,
        ]);
    }

    public function create(ListCategoryFoundationsAction $listCategoryFoundations): View
    {
        return view('bulkideationpromptstudio::create', [
            'categoryFoundations' => $listCategoryFoundations->handle(withFoundationDetails: false),
        ]);
    }

    public function store(Request $request, CreateBulkIdeationPromptAction $createPrompt): RedirectResponse
    {
        $limits = config('bulk_ideation_prompt_studio.limits', []);

        $data = $request->validate([
            'label' => ['required', 'string', 'max:'.($limits['label_max'] ?? 150)],
            'raw_inputs' => ['required', 'string', 'max:'.($limits['raw_inputs_max'] ?? 8000)],
            'business_goal' => ['nullable', 'string', 'max:'.($limits['business_goal_max'] ?? 500)],
            'post_category_uuid' => ['nullable', 'string', 'uuid', 'exists:post_categories,uuid'],
        ], [
            'label.required' => 'Vui lòng nhập tên gọi cho prompt.',
            'label.max' => 'Tên gọi không được vượt quá :max ký tự.',
            'raw_inputs.required' => 'Vui lòng dán ít nhất 1 từ khóa/ý tưởng vào kho nguyên liệu.',
            'raw_inputs.max' => 'Kho nguyên liệu không được vượt quá :max ký tự.',
            'business_goal.max' => 'Mục tiêu bài viết không được vượt quá :max ký tự.',
            'post_category_uuid.exists' => 'Chuyên mục được chọn không hợp lệ.',
        ]);

        $categoryId = isset($data['post_category_uuid'])
            ? PostCategory::where('uuid', $data['post_category_uuid'])->value('id')
            : null;

        $prompt = $createPrompt->handle(
            label: $data['label'],
            rawInputs: $data['raw_inputs'],
            businessGoal: $data['business_goal'] ?? null,
            postCategoryId: $categoryId,
            createdBy: $request->user()->id,
        );

        return redirect()->route('backend.bulkideationpromptstudio.show', $prompt)
            ->with('success', 'Đã sinh prompt — sao chép bên dưới để dùng.');
    }

    public function show(BulkIdeationPrompt $prompt): View
    {
        $prompt->load('category', 'createdBy');

        return view('bulkideationpromptstudio::show', [
            'prompt' => $prompt,
        ]);
    }

    public function destroy(BulkIdeationPrompt $prompt): RedirectResponse
    {
        $prompt->delete();

        return redirect()->route('backend.bulkideationpromptstudio.index')
            ->with('success', 'Đã xoá prompt.');
    }
}
