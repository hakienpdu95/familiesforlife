<?php

namespace Modules\KcItem\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Shared\Tenancy\TenantContext;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Modules\KcItem\Http\Resources\KcTagListResource;
use Modules\KcItem\Models\KcTag;
use Modules\KcItem\Queries\ListKcTagsHandler;
use Modules\KcItem\Queries\ListKcTagsQuery;

class KcTagApiController extends Controller
{
    public function index(Request $request, ListKcTagsHandler $handler): JsonResponse
    {
        $this->authorize('viewAny', KcTag::class);

        $sortRaw   = $request->input('sort.0');
        $sortField = is_array($sortRaw) ? (string) ($sortRaw['field'] ?? 'name') : 'name';
        $sortDir   = is_array($sortRaw) && ($sortRaw['dir'] ?? '') === 'desc' ? 'desc' : 'asc';

        $query = new ListKcTagsQuery(
            page:      max(1, (int) ($request->input('page', 1))),
            perPage:   min(200, max(5, (int) ($request->input('size', 50)))),
            sortField: $sortField,
            sortDir:   $sortDir,
            search:    $request->input('search'),
        );

        $paginator = $handler->handle($query);

        return response()->json([
            'data'      => KcTagListResource::collection($paginator->items()),
            'last_page' => $paginator->lastPage(),
            'total'     => $paginator->total(),
        ]);
    }

    /**
     * TomSelect endpoint — trả về danh sách tag của org để dùng trong form KcItem.
     * Không cần phân trang, lấy tất cả (max 500 tag).
     */
    public function forSelect(Request $request): JsonResponse
    {
        $this->authorize('viewAny', KcTag::class);

        $orgId = TenantContext::getOrganizationId();
        $q     = $request->input('q', '');

        $tags = KcTag::withoutTenant()
            ->where('organization_id', $orgId)
            ->when($q, fn ($query) => $query->where('name', 'like', '%' . $q . '%'))
            ->orderBy('name')
            ->limit(200)
            ->get(['id', 'name', 'slug', 'color_hex']);

        return response()->json($tags->map(fn ($t) => [
            'value' => $t->id,
            'text'  => $t->name,
            'color' => $t->color_hex,
            'slug'  => $t->slug,
        ]));
    }
}
