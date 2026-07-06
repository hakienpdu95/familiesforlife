<?php

namespace Modules\User\Http\Controllers\Api;

use App\Enums\RoleEnum;
use App\Http\Controllers\Controller;
use App\Models\User;
use App\Shared\Tenancy\TenantContext;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Modules\User\Http\Resources\UserListResource;
use Modules\User\Queries\ListUsersHandler;
use Modules\User\Queries\ListUsersQuery;

class UserApiController extends Controller
{
    public function index(Request $request, ListUsersHandler $handler): JsonResponse
    {
        $this->authorize('viewAny', User::class);

        $request->validate([
            'page'            => ['nullable', 'integer', 'min:1'],
            'size'            => ['nullable', 'integer', 'min:5', 'max:100'],
            'search'          => ['nullable', 'string', 'max:200'],
            'organization_id' => ['nullable', 'integer', 'min:1'],
            'role'            => ['nullable', 'string', Rule::in(collect(RoleEnum::cases())->map(fn ($r) => $r->value)->all())],
            'status'          => ['nullable', 'string', 'in:0,1'],
            'date_from'       => ['nullable', 'date_format:Y-m-d'],
            'date_to'         => ['nullable', 'date_format:Y-m-d', 'after_or_equal:date_from'],
        ]);

        $sortRaw   = $request->input('sort.0');
        $sortField = is_array($sortRaw) ? (string) ($sortRaw['field'] ?? 'created_at') : 'created_at';
        $sortDir   = is_array($sortRaw) && ($sortRaw['dir'] ?? '') === 'asc' ? 'asc' : 'desc';

        // Admin sees all (or filters by chosen org); non-admin is locked to their org
        $isAdmin = $request->user()->hasAnyRole(['super-admin', 'System_Admin']);

        if ($isAdmin) {
            $orgId = $request->filled('organization_id')
                ? (int) $request->input('organization_id')
                : null;
        } else {
            $orgId = TenantContext::getOrganizationId()
                  ?? $request->user()->organization_id;
        }

        $query = new ListUsersQuery(
            page:           max(1, $request->integer('page', 1)),
            perPage:        min(100, max(5, $request->integer('size', 25))),
            sortField:      $sortField,
            sortDir:        $sortDir,
            search:         $request->input('search'),
            organizationId: $orgId,
            role:           $request->input('role'),
            status:         $request->input('status'),
            dateFrom:       $request->input('date_from'),
            dateTo:         $request->input('date_to'),
        );

        $paginator = $handler->handle($query);

        return response()->json([
            'data'      => UserListResource::collection($paginator->items()),
            'last_page' => $paginator->lastPage(),
            'total'     => $paginator->total(),
        ]);
    }
}
