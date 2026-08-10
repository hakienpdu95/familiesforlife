<?php

namespace Modules\AIVideoStudioTemplate\Features\ProjectManagement\Http;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Modules\AIVideoStudioTemplate\Features\ProjectManagement\Http\Resources\ProjectListResource;
use Modules\AIVideoStudioTemplate\Features\ProjectManagement\Queries\ListProjectsForAdminHandler;
use Modules\AIVideoStudioTemplate\Features\ProjectManagement\Queries\ListProjectsForAdminQuery;
use Modules\AIVideoStudioTemplate\Models\AiVideoStudioProject;

/**
 * JSON backend cho Tabulator ở dashboard/ai-video-studio — cùng pattern
 * Modules/Post/app/Features/ArticleAuthoring/Http/ArticleApiController: remote
 * pagination/sort/filter, trả {data, last_page, total}. Gate phẳng bằng middleware
 * 'can:ai_video_studio_template.use' ở routes/web.php (không Policy riêng theo model).
 */
class ProjectApiController extends Controller
{
    public function index(Request $request, ListProjectsForAdminHandler $handler): JsonResponse
    {
        $validated = $request->validate([
            'page' => ['nullable', 'integer', 'min:1'],
            'size' => ['nullable', 'integer', 'min:5', 'max:100'],
            'search' => ['nullable', 'string', 'max:200'],
            'status' => ['nullable', 'string', 'in:'.implode(',', array_keys(AiVideoStudioProject::STATUSES))],
        ]);

        // Tabulator (sortMode: 'remote') gửi mảng sort[{field,dir}].
        $sortRaw = $request->input('sort.0');
        $sortField = is_array($sortRaw) ? (string) ($sortRaw['field'] ?? 'created_at') : 'created_at';
        $sortDir = is_array($sortRaw) && ($sortRaw['dir'] ?? '') === 'asc' ? 'asc' : 'desc';

        $query = new ListProjectsForAdminQuery(
            search: $validated['search'] ?? null,
            status: $validated['status'] ?? null,
            page: max(1, (int) ($validated['page'] ?? 1)),
            perPage: min(100, max(5, (int) ($validated['size'] ?? 25))),
            sortField: $sortField,
            sortDir: $sortDir,
        );

        $paginator = $handler->handle($query);

        return response()->json([
            'data' => ProjectListResource::collection($paginator->items()),
            'last_page' => $paginator->lastPage(),
            'total' => $paginator->total(),
        ]);
    }
}
