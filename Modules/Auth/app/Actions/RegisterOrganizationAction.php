<?php

namespace Modules\Auth\Actions;

use App\Enums\RoleEnum;
use App\Models\User;
use Modules\ActivityLog\Core\ActivityLogger;
use App\Shared\Tenancy\Models\Organization;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Lorisleiva\Actions\Concerns\AsAction;
use Modules\Auth\Data\RegisterOrganizationData;

/**
 * CQRS Command: Tạo Organization + owner User + gán role CEO.
 * Đây là command duy nhất được gọi khi một tổ chức đăng ký hệ thống.
 *
 * Sử dụng: RegisterOrganizationAction::run($data)
 */
class RegisterOrganizationAction
{
    use AsAction;

    public function handle(RegisterOrganizationData $data): User
    {
        return DB::transaction(function () use ($data): User {
            // 1. Tạo Organization — slug tự động từ Organization::boot()
            $organization = Organization::create([
                'name'     => $data->organization_name,
                'status'   => 'active',
                'settings' => [
                    'timezone' => 'Asia/Ho_Chi_Minh',
                    'locale'   => 'vi',
                ],
            ]);

            // 2. Tạo User chủ sở hữu
            $user = User::create([
                'name'            => $data->name,
                'email'           => $data->email,
                'password'        => Hash::make($data->password),
                'organization_id' => $organization->id,
            ]);

            // 3. Đặt owner_id cho Organization
            $organization->update(['owner_id' => $user->id]);

            // 4. Gán role CEO (tenant-scoped role)
            setPermissionsTeamId($organization->id);
            $user->assignRole(RoleEnum::CEO->value);
            setPermissionsTeamId(null);

            // 5. Ghi audit log
            ActivityLogger::info('Auth', 'organization_registered', $organization, [
                'organization_id'   => $organization->id,
                'organization_name' => $organization->name,
                'owner_email'       => $user->email,
            ]);

            return $user;
        });
    }
}
