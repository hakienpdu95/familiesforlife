<?php

namespace Modules\Approval\Actions;

use App\Models\User;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Lorisleiva\Actions\Concerns\AsAction;

class ListPlatformUsersAction
{
    use AsAction;

    private const SORTABLE = ['name', 'email', 'created_at'];

    public function handle(
        ?string $search = null,
        ?string $role = null,
        ?string $status = null,
        string $sortField = 'name',
        string $sortDir = 'asc',
        int $perPage = 25,
        int $page = 1,
    ): LengthAwarePaginator {
        return User::withoutGlobalScopes()
            ->whereNull('organization_id')
            ->with('roles:id,name')
            ->when($search, fn ($q) => $q->where(fn ($w) => $w->where('name', 'like', "%{$search}%")->orWhere('email', 'like', "%{$search}%")))
            ->when($role, fn ($q) => $q->whereHas('roles', fn ($r) => $r->where('name', $role)))
            ->when($status === 'active', fn ($q) => $q->where('is_active', true))
            ->when($status === 'inactive', fn ($q) => $q->where('is_active', false))
            ->orderBy(in_array($sortField, self::SORTABLE, true) ? $sortField : 'name', $sortDir === 'desc' ? 'desc' : 'asc')
            ->orderBy('id')
            ->paginate($perPage, ['*'], 'page', $page);
    }
}
