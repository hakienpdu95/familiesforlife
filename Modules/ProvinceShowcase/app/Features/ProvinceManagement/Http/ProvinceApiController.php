<?php

namespace Modules\ProvinceShowcase\Features\ProvinceManagement\Http;

use App\Http\Controllers\Controller;
use App\Models\Province;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Modules\ProvinceShowcase\Enums\ProvincePlaceType;
use Modules\ProvinceShowcase\Features\ProvinceManagement\Http\Resources\ProvinceListResource;
use Modules\ProvinceShowcase\Features\ProvinceManagement\Queries\ListProvincesForAdminHandler;
use Modules\ProvinceShowcase\Features\ProvinceManagement\Queries\ListProvincesForAdminQuery;

class ProvinceApiController extends Controller
{
    public function index(Request $request, ListProvincesForAdminHandler $handler): JsonResponse
    {
        $this->authorize('viewAny', Province::class);

        $validated = $request->validate([
            'page' => ['nullable', 'integer', 'min:1'],
            'size' => ['nullable', 'integer', 'min:5', 'max:100'],
            'search' => ['nullable', 'string', 'max:200'],
            'region_id' => ['nullable', 'integer'],
            'place_type' => ['nullable', Rule::enum(ProvincePlaceType::class)],
            'is_active' => ['nullable', 'in:0,1'],
            'is_featured' => ['nullable', 'in:0,1'],
        ]);

        $sortRaw = $request->input('sort.0');
        $sortField = is_array($sortRaw) ? (string) ($sortRaw['field'] ?? 'province_code') : 'province_code';
        $sortDir = is_array($sortRaw) && ($sortRaw['dir'] ?? '') === 'desc' ? 'desc' : 'asc';

        $paginator = $handler->handle(new ListProvincesForAdminQuery(
            page: max(1, (int) ($validated['page'] ?? 1)),
            perPage: min(100, max(5, (int) ($validated['size'] ?? 25))),
            search: $validated['search'] ?? null,
            regionId: isset($validated['region_id']) ? (int) $validated['region_id'] : null,
            placeType: $validated['place_type'] ?? null,
            isActive: isset($validated['is_active']) ? (bool) (int) $validated['is_active'] : null,
            isFeatured: isset($validated['is_featured']) ? (bool) (int) $validated['is_featured'] : null,
            sortField: $sortField,
            sortDir: $sortDir,
        ));

        return response()->json([
            'data' => ProvinceListResource::collection($paginator->items()),
            'last_page' => $paginator->lastPage(),
            'total' => $paginator->total(),
        ]);
    }
}
