<?php

namespace Modules\Organization\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Modules\Organization\Http\Resources\OrganizationListResource;
use Modules\Organization\Models\Organization;
use Modules\Organization\Queries\ListOrganizationsHandler;
use Modules\Organization\Queries\ListOrganizationsQuery;

class OrganizationApiController extends Controller
{
    public function index(Request $request, ListOrganizationsHandler $handler): JsonResponse
    {
        $this->authorize('viewAny', Organization::class);

        $validated = $request->validate([
            'page'          => ['nullable', 'integer', 'min:1'],
            'size'          => ['nullable', 'integer', 'min:5', 'max:100'],
            'search'        => ['nullable', 'string', 'max:200'],
            'province_code' => ['nullable', 'string', 'max:10'],
            'ward_code'     => ['nullable', 'string', 'max:10'],
            'status'        => ['nullable', 'string', 'in:active,suspended,inactive'],
            'date_from'     => ['nullable', 'date_format:Y-m-d'],
            'date_to'       => ['nullable', 'date_format:Y-m-d', 'after_or_equal:date_from'],
        ]);

        // Guard against non-array sort[0] payload
        $sortRaw   = $request->input('sort.0');
        $sortField = is_array($sortRaw) ? (string) ($sortRaw['field'] ?? 'created_at') : 'created_at';
        $sortDir   = is_array($sortRaw) && ($sortRaw['dir'] ?? '') === 'asc' ? 'asc' : 'desc';

        $query = new ListOrganizationsQuery(
            page:         max(1, (int) ($validated['page'] ?? 1)),
            perPage:      min(100, max(5, (int) ($validated['size'] ?? 25))),
            sortField:    $sortField,
            sortDir:      $sortDir,
            search:       $validated['search'] ?? null,
            provinceCode: $validated['province_code'] ?? null,
            wardCode:     $validated['ward_code'] ?? null,
            dateFrom:     $validated['date_from'] ?? null,
            dateTo:       $validated['date_to'] ?? null,
            status:       $validated['status'] ?? null,
        );

        $paginator = $handler->handle($query);

        return response()->json([
            'data'      => OrganizationListResource::collection($paginator->items()),
            'last_page' => $paginator->lastPage(),
            'total'     => $paginator->total(),
        ]);
    }
}
