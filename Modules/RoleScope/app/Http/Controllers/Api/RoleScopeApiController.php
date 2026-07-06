<?php

namespace Modules\RoleScope\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Modules\RoleScope\Http\Resources\UserRoleScopeListResource;
use Modules\RoleScope\Models\UserRoleScope;
use Modules\RoleScope\Queries\ListUserRoleScopesHandler;
use Modules\RoleScope\Queries\ListUserRoleScopesQuery;

class RoleScopeApiController extends Controller
{
    public function index(Request $request, ListUserRoleScopesHandler $handler): JsonResponse
    {
        $this->authorize('viewAny', UserRoleScope::class);

        $validated = $request->validate([
            'page'             => ['nullable', 'integer', 'min:1'],
            'size'             => ['nullable', 'integer', 'min:5', 'max:100'],
            'search'           => ['nullable', 'string', 'max:200'],
            'role_id'          => ['nullable', 'integer'],
            'scope_branch_id'  => ['nullable', 'integer'],
            'scope_dept_id'    => ['nullable', 'integer'],
            'scope_level'      => ['nullable', 'string', 'in:org,branch,dept'],
            'status'           => ['nullable', 'string', 'in:active,expired'],
        ]);

        $sortRaw   = $request->input('sort.0');
        $sortField = is_array($sortRaw) ? (string) ($sortRaw['field'] ?? 'granted_at') : 'granted_at';
        $sortDir   = is_array($sortRaw) && ($sortRaw['dir'] ?? '') === 'asc' ? 'asc' : 'desc';

        $query = new ListUserRoleScopesQuery(
            page:           max(1, (int) ($validated['page'] ?? 1)),
            perPage:        min(100, max(5, (int) ($validated['size'] ?? 25))),
            sortField:      $sortField,
            sortDir:        $sortDir,
            search:         $validated['search'] ?? null,
            orgId:          auth()->user()->organization_id ?: null,
            roleId:         isset($validated['role_id']) ? (int) $validated['role_id'] : null,
            scopeBranchId:  isset($validated['scope_branch_id']) ? (int) $validated['scope_branch_id'] : null,
            scopeDeptId:    isset($validated['scope_dept_id']) ? (int) $validated['scope_dept_id'] : null,
            scopeLevel:     $validated['scope_level'] ?? null,
            status:         $validated['status'] ?? null,
        );

        $paginator = $handler->handle($query);

        return response()->json([
            'data'      => UserRoleScopeListResource::collection($paginator->items()),
            'last_page' => $paginator->lastPage(),
            'total'     => $paginator->total(),
        ]);
    }
}
