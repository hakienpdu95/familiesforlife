<?php

namespace Modules\User\Actions;

use App\Enums\RoleEnum;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Lorisleiva\Actions\Concerns\AsAction;
use Modules\Organization\Models\OrganizationMember;
use Modules\User\Data\UpdateUserData;
use Modules\User\Events\UserRoleAssigned;
use Spatie\Permission\PermissionRegistrar;

class UpdateUserAction
{
    use AsAction;

    public function handle(User $user, UpdateUserData $data): User
    {
        return DB::transaction(function () use ($user, $data): User {
            $previousRole = $user->getRoleNames()->first();

            $updateData = [
                'name'            => $data->name,
                'email'           => $data->email,
                'organization_id' => $data->organization_id,
                'department'      => $data->department,
                'is_active'       => $data->is_active,
            ];

            if (! empty($data->password)) {
                $updateData['password'] = Hash::make($data->password);
            }

            $user->fill($updateData)->save();

            // ── Org membership ──────────────────────────────────────────
            $orgRole    = $this->deriveOrgRole($data->system_role);
            $membership = OrganizationMember::where('organization_id', $data->organization_id)
                ->where('user_id', $user->id)
                ->first();

            if ($membership) {
                // Never downgrade an org owner via this form
                if ($membership->role !== OrganizationMember::ROLE_OWNER) {
                    $membership->update(['role' => $orgRole]);
                }
            } else {
                OrganizationMember::create([
                    'organization_id' => $data->organization_id,
                    'user_id'         => $user->id,
                    'role'            => $orgRole,
                    'joined_at'       => now(),
                ]);
            }

            // ── Spatie role sync ────────────────────────────────────────
            $prevTeamId = getPermissionsTeamId();
            setPermissionsTeamId($data->organization_id);
            $user->syncRoles([$data->system_role]);
            setPermissionsTeamId($prevTeamId);
            app(PermissionRegistrar::class)->forgetCachedPermissions();

            // Fire role-assigned event only when role actually changed
            if ($previousRole !== $data->system_role) {
                event(new UserRoleAssigned($user, $data->system_role));
            }

            return $user;
        });
    }

    private function deriveOrgRole(string $systemRole): string
    {
        return in_array($systemRole, [RoleEnum::CEO->value, RoleEnum::ADMIN->value], true)
            ? 'admin'
            : 'member';
    }
}
