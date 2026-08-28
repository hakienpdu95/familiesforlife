<?php

namespace Modules\PromptFrameworkStudio\Features\PromptGeneration\Http;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Modules\Post\Models\PostCategory;
use Modules\PromptFrameworkStudio\Features\Concerns\ResolvesCategoryFoundation;
use Modules\PromptFrameworkStudio\Features\PromptGeneration\Actions\BuildEditorialContextBlockAction;
use Modules\PromptFrameworkStudio\Features\PromptGeneration\Actions\BuildFamilyValuesBlockAction;
use Modules\PromptFrameworkStudio\Features\PromptGeneration\Actions\FindLatestPromptForFrameworkAction;
use Modules\PromptFrameworkStudio\Features\PromptGeneration\Actions\FindSimilarSeedKeywordPromptsAction;
use Modules\PromptFrameworkStudio\Features\PromptGeneration\Http\Resources\GeneratedPromptListResource;
use Modules\PromptFrameworkStudio\Features\PromptGeneration\Queries\ListGeneratedPromptsForAdminHandler;
use Modules\PromptFrameworkStudio\Features\PromptGeneration\Queries\ListGeneratedPromptsForAdminQuery;

/** JSON backend cho Tabulator ở dashboard/prompt-studio/prompts — cùng pattern ContentOutlineApiController. */
class PromptGenerationApiController extends Controller
{
    use ResolvesCategoryFoundation;

    public function index(Request $request, ListGeneratedPromptsForAdminHandler $handler): JsonResponse
    {
        $validated = $request->validate([
            'page' => ['nullable', 'integer', 'min:1'],
            'size' => ['nullable', 'integer', 'min:5', 'max:100'],
            'search' => ['nullable', 'string', 'max:150'],
            'framework_key' => ['nullable', 'string', 'max:30'],
        ]);

        $sortRaw = $request->input('sort.0');
        $sortField = is_array($sortRaw) ? (string) ($sortRaw['field'] ?? 'updated_at') : 'updated_at';
        $sortDir = is_array($sortRaw) && ($sortRaw['dir'] ?? '') === 'asc' ? 'asc' : 'desc';

        $query = new ListGeneratedPromptsForAdminQuery(
            search: $validated['search'] ?? null,
            frameworkKey: $validated['framework_key'] ?? null,
            page: max(1, (int) ($validated['page'] ?? 1)),
            perPage: min(100, max(5, (int) ($validated['size'] ?? 20))),
            sortField: $sortField,
            sortDir: $sortDir,
        );

        $paginator = $handler->handle($query);

        return response()->json([
            'data' => GeneratedPromptListResource::collection($paginator->items()),
            'last_page' => $paginator->lastPage(),
            'total' => $paginator->total(),
        ]);
    }

    /**
     * §4.4 (v2.7) — trả về CHÍNH đoạn text sẽ được chèn vào prompt cho chuyên mục này, để bản xem
     * trước ở form là WYSIWYG thật chứ không phải bản mô phỏng lạc quan hơn kết quả cuối.
     *
     * Vì sao có endpoint riêng thay vì gọi thẳng API của ContentFoundation như ContentOutlines làm:
     *   1. API kia gác bởi permission RIÊNG `content_foundation.use` — người chỉ có
     *      `prompt_framework_studio.use` sẽ bị 403 ngay giữa form (2 seeder hiện cùng cấp cho 3
     *      role giống nhau, nhưng đó là TRÙNG HỢP về dữ liệu seed, không phải hợp đồng — dựa vào nó
     *      là để lộ 1 lỗi 403 chờ sẵn khi ai đó chỉnh phân quyền).
     *   2. API kia trả về dữ liệu THÔ theo từng field; client sẽ phải tự ghép lại thành đoạn text —
     *      tức là logic ghép tồn tại 2 bản (PHP + JS) và chắc chắn trôi lệch nhau. Ở đây server ghép
     *      1 lần bằng đúng Action mà luồng sinh thật dùng.
     */
    public function editorialContext(PostCategory $category, BuildEditorialContextBlockAction $buildContext, BuildFamilyValuesBlockAction $buildFamilyValues): JsonResponse
    {
        $foundation = $this->resolveFoundation($category->id);

        $blocks = array_values(array_filter([
            $buildContext->handle($foundation, $category->name),
            $foundation ? $buildFamilyValues->handle($foundation) : '',
        ], fn (string $b): bool => $b !== ''));

        return response()->json([
            'has_foundation' => $foundation !== null,
            'category_name' => $category->name,
            'block' => implode("\n\n", $blocks),
        ]);
    }

    /**
     * spec/AIIdeaMatrixGenerator.md §3 — "Dùng lại giá trị từ prompt trước". `frameworkKey` là
     * chuỗi thô (không phải route-model-binding — không có model nào cho khoá framework), validate
     * bằng chính danh sách config thay vì để lọt query DB với khoá bất kỳ.
     */
    public function lastPromptForFramework(string $frameworkKey, FindLatestPromptForFrameworkAction $findLatest): JsonResponse
    {
        if (! array_key_exists($frameworkKey, config('prompt_framework_studio.frameworks', []))) {
            return response()->json(['found' => false]);
        }

        $prompt = $findLatest->handle($frameworkKey);

        return response()->json([
            'found' => $prompt !== null,
            'field_values' => $prompt?->field_values ?? [],
        ]);
    }

    /**
     * (2026-08-28, phản hồi review spec/TopicClusterGenerator.md) — Keyword Cannibalization: gọi
     * mỗi khi người dùng gõ "Từ khóa hạt giống" ở framework `topiccluster` (debounce phía JS), trả
     * về các GeneratedPrompt khác đã dùng từ khóa giống/gần giống. `exclude_uuid` dùng ở trang sửa
     * để không tự cảnh báo với chính bản ghi đang sửa.
     */
    public function similarSeedKeywords(Request $request, FindSimilarSeedKeywordPromptsAction $findSimilar): JsonResponse
    {
        $validated = $request->validate([
            'seed_keyword' => ['required', 'string', 'max:255'],
            'exclude_uuid' => ['nullable', 'string', 'uuid'],
        ]);

        $matches = $findSimilar->handle($validated['seed_keyword'], $validated['exclude_uuid'] ?? null);

        return response()->json([
            'matches' => array_map(fn (array $m): array => [
                'uuid' => $m['uuid'],
                'label' => $m['label'],
                'seed_keyword' => $m['seed_keyword'],
                'created_at' => $m['created_at'],
                'show_url' => route('backend.promptstudio.prompts.show', $m['uuid']),
            ], $matches),
        ]);
    }
}
