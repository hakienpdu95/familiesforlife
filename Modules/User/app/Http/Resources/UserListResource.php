<?php

namespace Modules\User\Http\Resources;

use App\Enums\RoleEnum;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Spatie\Permission\PermissionRegistrar;

class UserListResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        // Role lookup must use the target user's org as team context, not the
        // current viewer's. A super-admin (null context) viewing org-scoped
        // users would otherwise get empty results.
        $spatiRole = $this->resolveSystemRole();
        $roleEnum  = $spatiRole ? RoleEnum::tryFrom($spatiRole) : null;

        return [
            'id'                => $this->id,
            'name'              => $this->name,
            'email'             => $this->email,
            'department'        => $this->department,
            'avatar_url'        => 'https://api.dicebear.com/9.x/initials/svg?seed=' . urlencode($this->name)
                                    . '&backgroundColor=6366f1&fontFamily=Arial&fontSize=40&fontWeight=700',

            'organization_id'   => $this->organization_id,
            'organization_name' => $this->organization?->name,

            'role'              => $roleEnum?->value ?? $spatiRole,
            'role_label'        => $roleEnum?->label() ?? ($spatiRole ?? '—'),
            'role_badge'        => $roleEnum?->badgeClass() ?? 'badge-ghost',

            'is_active'         => $this->is_active,
            'status_label'      => $this->is_active ? 'Hoạt động' : 'Vô hiệu',
            'status_badge'      => $this->is_active ? 'badge-success' : 'badge-ghost',

            'can_delete'        => $request->user()?->id !== $this->id,

            'created_at'        => $this->created_at?->format('d/m/Y'),

            'edit_url'          => route('backend.users.edit',    $this->resource),
            'delete_url'        => route('backend.users.destroy', $this->resource),
        ];
    }

    /**
     * Get the user's system role (RoleEnum) name without being affected by
     * the current request's team context. We temporarily scope to THIS user's
     * organisation so the query matches regardless of who is viewing the list
     * (e.g. a super-admin whose own team context is null).
     */
    private function resolveSystemRole(): ?string
    {
        $registrar   = app(PermissionRegistrar::class);
        $savedTeamId = getPermissionsTeamId();

        try {
            // Clear any cached roles loaded under the viewer's team context.
            $this->unsetRelation('roles');

            // Scope to the target user's own organisation.
            setPermissionsTeamId($this->organization_id);

            return $this->getRoleNames()->first();
        } finally {
            // Always restore — even on exception — so nothing downstream breaks.
            setPermissionsTeamId($savedTeamId);
            $this->unsetRelation('roles');
        }
    }
}
