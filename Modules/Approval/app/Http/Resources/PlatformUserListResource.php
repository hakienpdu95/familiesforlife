<?php

namespace Modules\Approval\Http\Resources;

use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Support\Facades\Gate;

class PlatformUserListResource extends JsonResource
{
    private const ROLE_BADGES = [
        'super-admin' => 'badge-error',
        'platform_content_head' => 'badge-primary',
        'platform_content_editor' => 'badge-info',
        'platform_content_creator' => 'badge-accent',
        'platform_section_editor' => 'badge-secondary',
        'platform_content_moderator' => 'badge-warning',
        'platform_ops' => 'badge-success',
        'platform_viewer' => 'badge-neutral',
    ];

    public function toArray(Request $request): array
    {
        $roleName = $this->roles->first()?->name;

        return [
            'id' => $this->id,
            'name' => $this->name,
            'email' => $this->email,
            'role_label' => User::platformRoleLabels()[$roleName] ?? $roleName,
            'role_badge' => self::ROLE_BADGES[$roleName] ?? 'badge-ghost',
            'is_active' => (bool) $this->is_active,
            'created_at' => $this->created_at?->format('d/m/Y'),
            'can_update' => Gate::allows('platform-users.update', $this->resource),
            'can_reset_password' => Gate::allows('platform-users.resetPassword', $this->resource),
            'can_deactivate' => Gate::allows('platform-users.deactivate', $this->resource),
            'can_activate' => Gate::allows('platform-users.activate', $this->resource),
            'edit_url' => route('backend.platform-users.edit', $this->resource),
            'reset_password_url' => route('backend.platform-users.reset-password', $this->resource),
            'destroy_url' => route('backend.platform-users.destroy', $this->resource),
            'activate_url' => route('backend.platform-users.activate', $this->resource),
        ];
    }
}
