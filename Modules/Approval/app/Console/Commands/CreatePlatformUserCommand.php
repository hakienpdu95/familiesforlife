<?php

namespace Modules\Approval\Console\Commands;

use App\Models\User;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;
use Modules\ActivityLog\Core\ActivityLogger;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;

/**
 * Giải pháp tạm thời thay UI quản trị "Quản lý nhân sự Platform" (chưa xây, xem
 * spec/Platform_RBAC_Technical_Specification.md §3.8) — CHỈ để tạo nhân sự biên tập/vận
 * hành (platform_content_head/editor/moderator, platform_ops, platform_viewer), có audit log,
 * thay vì chạy tinker tay không để lại vết.
 *
 * CỐ Ý KHÔNG cho tạo `super-admin` qua đây — role đó nhạy cảm nhất (§0/§3.6, bypass toàn
 * cục), trong khi command CLI không kiểm tra được "ai đang chạy nó". Cần `super-admin` mới
 * thì đi qua `Modules/Auth/database/seeders/AuthDatabaseSeeder.php` (thủ công, có review),
 * không qua tool tự động này.
 */
class CreatePlatformUserCommand extends Command
{
    protected $signature = 'platform:user-create
        {email : Email đăng nhập}
        {name : Họ và tên hiển thị}
        {role : platform_content_head|platform_content_editor|platform_content_creator|platform_section_editor|platform_content_moderator|platform_ops|platform_viewer}
        {--password= : Mật khẩu — bỏ trống sẽ tự sinh ngẫu nhiên và in ra màn hình}';

    protected $description = 'Tạo tài khoản nhân sự biên tập/vận hành Platform (organization_id=null) — không tạo được super-admin';

    private const ALLOWED_ROLES = [
        'platform_content_head',
        'platform_content_editor',
        'platform_content_creator',
        'platform_section_editor',
        'platform_content_moderator',
        'platform_ops',
        'platform_viewer',
    ];

    public function handle(): int
    {
        $email    = $this->argument('email');
        $name     = $this->argument('name');
        $role     = $this->argument('role');
        $password = $this->option('password') ?: Str::random(16);

        $validator = Validator::make(compact('email', 'name', 'role'), [
            'email' => ['required', 'email', 'max:255', 'unique:users,email'],
            'name'  => ['required', 'string', 'max:255'],
            'role'  => ['required', 'in:' . implode(',', self::ALLOWED_ROLES)],
        ]);

        if ($validator->fails()) {
            $this->error('Dữ liệu không hợp lệ:');
            foreach ($validator->errors()->all() as $message) {
                $this->line("  - {$message}");
            }

            return self::FAILURE;
        }

        if (! Role::where('name', $role)->where('guard_name', 'web')->exists()) {
            $this->error("Role \"{$role}\" chưa tồn tại trong bảng roles — chạy seeder tương ứng trước.");

            return self::FAILURE;
        }

        // User::$fillable khai báo qua PHP attribute #[Fillable(...)] không gồm
        // email_verified_at — create() dùng fill() thường sẽ ném MassAssignmentException
        // (đúng ý preventSilentlyDiscardingAttributes). Theo đúng quy ước forceFill() các
        // seeder Platform khác đang dùng (vd ContentModeratorSeeder) thay vì create() trần.
        $user = new User();
        $user->forceFill([
            'name'              => $name,
            'email'             => $email,
            'password'          => Hash::make($password),
            'organization_id'   => null,
            'email_verified_at' => now(),
        ])->save();

        // Platform role KHÔNG team-scoped — setPermissionsTeamId(null) tường minh trước khi
        // gán, đúng quy ước ContentModeratorSeeder đang dùng (không dựa vào ambient context).
        setPermissionsTeamId(null);
        $user->assignRole($role);

        app(PermissionRegistrar::class)->forgetCachedPermissions();

        ActivityLogger::info('User', 'platform_user_created', $user, [
            'email'       => $email,
            'role'        => $role,
            'created_via' => 'artisan platform:user-create',
        ]);

        $roleLabel = User::platformRoleLabels()[$role] ?? $role;
        $this->info("✓ Đã tạo user Platform: {$email} (role: {$role} — {$roleLabel}).");

        if (! $this->option('password')) {
            $this->warn("Mật khẩu tự sinh — đổi ngay sau khi đăng nhập lần đầu: {$password}");
        }

        return self::SUCCESS;
    }
}
