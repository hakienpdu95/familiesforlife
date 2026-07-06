<?php

namespace Modules\RoleScope\Queries;

use App\Shared\Contracts\QueryInterface;

class ListUserRoleScopesQuery implements QueryInterface
{
    public function __construct(
        public readonly int     $page      = 1,
        public readonly int     $perPage   = 25,
        public readonly string  $sortField = 'granted_at',
        public readonly string  $sortDir   = 'desc',

        // Text search — user name, email, role name
        public readonly ?string $search        = null,

        // Exact filters
        public readonly ?int    $orgId          = null, // null = all orgs (admin)
        public readonly ?int    $roleId         = null,
        public readonly ?int    $scopeBranchId  = null,
        public readonly ?int    $scopeDeptId    = null,
        public readonly ?string $scopeLevel     = null, // org | branch | dept
        public readonly ?string $status         = null, // active | expired
    ) {}
}
