<?php

namespace Modules\RoleScope\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class UserRoleScopeListResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        $isExpired = $this->expires_at !== null && $this->expires_at->isPast();

        return [
            'id'           => $this->id,
            'user_id'      => $this->user_id,
            'user_name'    => $this->user?->name ?? '—',
            'user_email'   => $this->user?->email ?? '—',
            'role_id'      => $this->role_id,
            'role_name'    => $this->role?->name ?? '—',

            'scope_level'        => $this->scope_level,
            'scope_branch_id'    => $this->scope_branch_id,
            'scope_branch_name'  => $this->scopeBranch?->name ?? null,
            'scope_branch_code'  => $this->scopeBranch?->code ?? null,
            'scope_dept_id'      => $this->scope_dept_id,
            'scope_dept_name'    => $this->scopeDept?->name ?? null,
            'scope_dept_code'    => $this->scopeDept?->code ?? null,

            'granted_by_name' => $this->grantedByUser?->name ?? '—',
            'granted_at'      => $this->granted_at?->format('d/m/Y H:i'),
            'expires_at'      => $this->expires_at?->format('d/m/Y H:i'),
            'is_expired'      => $isExpired,
            'note'            => $this->note,

            'show_url'   => route('backend.role-scopes.show', $this->resource),
            'edit_url'   => route('backend.role-scopes.edit', $this->resource),
            'delete_url' => route('backend.role-scopes.destroy', $this->resource),
        ];
    }
}
