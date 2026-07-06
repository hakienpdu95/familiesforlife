<?php

namespace Modules\RoleScope\Queries;

use App\Shared\Contracts\QueryHandlerInterface;
use App\Shared\Contracts\QueryInterface;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Builder;
use Modules\RoleScope\Models\UserRoleScope;

class ListUserRoleScopesHandler implements QueryHandlerInterface
{
    private const SORTABLE = [
        'granted_at', 'expires_at', 'user_name', 'role_name',
    ];

    public function handle(QueryInterface $query): LengthAwarePaginator
    {
        /** @var ListUserRoleScopesQuery $query */

        $sortField = in_array($query->sortField, self::SORTABLE, true)
            ? $query->sortField
            : 'granted_at';

        $sortDir = $query->sortDir === 'asc' ? 'asc' : 'desc';

        $q = UserRoleScope::withoutTenant()
            ->select('user_role_scopes.*')
            ->when($query->orgId, fn ($b, $id) => $b->where('user_role_scopes.organization_id', $id))
            ->with([
                'user:id,name,email',
                'role:id,name',
                'scopeBranch:id,name,code',
                'scopeDept:id,name,code',
                'grantedByUser:id,name',
            ]);

        // ── Text search ──────────────────────────────────────────────────────
        if ($query->search !== null && $query->search !== '') {
            $term = '%' . $query->search . '%';
            $q->where(function (Builder $sub) use ($term): void {
                $sub->whereHas('user', function (Builder $u) use ($term): void {
                    $u->where('name', 'like', $term)
                      ->orWhere('email', 'like', $term);
                })->orWhereHas('role', function (Builder $r) use ($term): void {
                    $r->where('name', 'like', $term);
                });
            });
        }

        // ── Exact filters ────────────────────────────────────────────────────
        if ($query->roleId !== null) {
            $q->where('user_role_scopes.role_id', $query->roleId);
        }

        if ($query->scopeBranchId !== null) {
            $q->where('user_role_scopes.scope_branch_id', $query->scopeBranchId);
        }

        if ($query->scopeDeptId !== null) {
            $q->where('user_role_scopes.scope_dept_id', $query->scopeDeptId);
        }

        if ($query->scopeLevel !== null && $query->scopeLevel !== '') {
            match ($query->scopeLevel) {
                'org'    => $q->whereNull('user_role_scopes.scope_branch_id')
                              ->whereNull('user_role_scopes.scope_dept_id'),
                'branch' => $q->whereNotNull('user_role_scopes.scope_branch_id')
                              ->whereNull('user_role_scopes.scope_dept_id'),
                'dept'   => $q->whereNotNull('user_role_scopes.scope_dept_id'),
                default  => null,
            };
        }

        if ($query->status !== null && $query->status !== '') {
            if ($query->status === 'active') {
                $q->where(function (Builder $sub): void {
                    $sub->whereNull('expires_at')->orWhere('expires_at', '>', now());
                });
            } elseif ($query->status === 'expired') {
                $q->whereNotNull('expires_at')->where('expires_at', '<=', now());
            }
        }

        // ── Sort ─────────────────────────────────────────────────────────────
        match ($sortField) {
            'user_name' => $q->leftJoin('users as sort_users', 'user_role_scopes.user_id', '=', 'sort_users.id')
                             ->orderBy('sort_users.name', $sortDir),
            'role_name' => $q->leftJoin('roles as sort_roles', 'user_role_scopes.role_id', '=', 'sort_roles.id')
                             ->orderBy('sort_roles.name', $sortDir),
            default     => $q->orderBy('user_role_scopes.' . $sortField, $sortDir),
        };

        $q->orderBy('user_role_scopes.id', $sortDir);

        return $q->paginate($query->perPage, ['*'], 'page', $query->page);
    }
}
