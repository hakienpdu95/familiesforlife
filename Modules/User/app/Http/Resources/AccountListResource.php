<?php

namespace Modules\User\Http\Resources;

use App\Enums\RoleEnum;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class AccountListResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        $roleName = $this->roles->first()?->name;
        $role = $roleName ? RoleEnum::tryFrom($roleName) : null;
        $actor = $request->user();

        return [
            'id' => $this->id,
            'name' => $this->name,
            'email' => $this->email,
            'username' => $this->username,
            'phone' => $this->phone,
            'job_title' => $this->job_title,
            'department' => $this->department,
            'role_label' => $role?->label() ?? $roleName,
            'role_badge' => $role?->badgeClass() ?? 'badge-ghost',
            'is_active' => (bool) $this->is_active,
            'created_at' => $this->created_at?->format('d/m/Y'),
            'last_active_at' => $this->last_active_at?->format('d/m/Y H:i'),
            'can_update' => $actor->can('update', $this->resource),
            'can_reset' => $actor->can('sendPasswordResetLink', $this->resource),
            'can_deactivate' => $actor->can('deactivate', $this->resource),
            'can_activate' => $actor->can('activate', $this->resource),
            'edit_url' => route('backend.users.edit', $this->resource),
            'reset_url' => route('backend.users.password-reset-link', $this->resource),
            'destroy_url' => route('backend.users.destroy', $this->resource),
            'activate_url' => route('backend.users.activate', $this->resource),
        ];
    }
}
