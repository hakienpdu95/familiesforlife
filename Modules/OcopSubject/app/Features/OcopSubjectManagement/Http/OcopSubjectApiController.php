<?php

namespace Modules\OcopSubject\Features\OcopSubjectManagement\Http;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Modules\OcopSubject\Enums\OcopSubjectOrganizationType;
use Modules\OcopSubject\Features\OcopSubjectManagement\Http\Resources\OcopSubjectListResource;
use Modules\OcopSubject\Features\OcopSubjectManagement\Queries\ListOcopSubjectsForAdminHandler;
use Modules\OcopSubject\Features\OcopSubjectManagement\Queries\ListOcopSubjectsForAdminQuery;
use Modules\OcopSubject\Models\OcopSubject;

class OcopSubjectApiController extends Controller
{
    public function index(Request $request, ListOcopSubjectsForAdminHandler $handler): JsonResponse
    {
        $this->authorize('viewAny', OcopSubject::class);

        $validated = $request->validate([
            'page' => ['nullable', 'integer', 'min:1'],
            'size' => ['nullable', 'integer', 'min:5', 'max:100'],
            'search' => ['nullable', 'string', 'max:200'],
            'organization_type' => ['nullable', Rule::enum(OcopSubjectOrganizationType::class)],
            'province_code' => ['nullable', 'string', 'size:2'],
        ]);

        $sortRaw = $request->input('sort.0');
        $sortField = is_array($sortRaw) ? (string) ($sortRaw['field'] ?? 'created_at') : 'created_at';
        $sortDir = is_array($sortRaw) && ($sortRaw['dir'] ?? '') === 'asc' ? 'asc' : 'desc';

        $paginator = $handler->handle(new ListOcopSubjectsForAdminQuery(
            search: $validated['search'] ?? null,
            organizationType: $validated['organization_type'] ?? null,
            provinceCode: $validated['province_code'] ?? null,
            page: max(1, (int) ($validated['page'] ?? 1)),
            perPage: min(100, max(5, (int) ($validated['size'] ?? 20))),
            sortField: $sortField,
            sortDir: $sortDir,
        ));

        return response()->json([
            'data' => OcopSubjectListResource::collection($paginator->items()),
            'last_page' => $paginator->lastPage(),
            'total' => $paginator->total(),
        ]);
    }
}
