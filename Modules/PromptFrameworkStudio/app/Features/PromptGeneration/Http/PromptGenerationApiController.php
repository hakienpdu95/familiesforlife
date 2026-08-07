<?php

namespace Modules\PromptFrameworkStudio\Features\PromptGeneration\Http;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Modules\Post\Models\PostCategory;
use Modules\PromptFrameworkStudio\Features\Concerns\ResolvesCategoryFoundation;
use Modules\PromptFrameworkStudio\Features\PromptGeneration\Actions\BuildEditorialContextBlockAction;
use Modules\PromptFrameworkStudio\Features\PromptGeneration\Actions\BuildFamilyValuesBlockAction;
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
}
