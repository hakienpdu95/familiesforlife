<?php

namespace Modules\Approval\Console\Commands;

use App\Models\User;
use Illuminate\Console\Command;

/**
 * Audit đơn giản (spec/Platform_RBAC_Technical_Specification.md §3.6) — liệt kê mọi user có
 * role `super-admin` hoặc `platform_*` mà `organization_id !== null`. Không phải Observer,
 * chỉ chạy tay/định kỳ (cron/CI) để phát hiện sớm nếu có role Lớp A bị gán nhầm cho user
 * thuộc 1 tổ chức — đúng lớp phòng vệ mà guard ở AuthDatabaseSeeder không tự phủ hết (guard
 * đó chỉ chặn lúc seed, không chặn các đường gán role khác trong tương lai).
 */
class AuditPlatformRoleScopeCommand extends Command
{
    protected $signature = 'platform:audit-role-scope';

    protected $description = 'Liệt kê user có Platform Role (super-admin/platform_*) nhưng organization_id khác null';

    private const PLATFORM_ROLE_PREFIXES = ['super-admin', 'platform_'];

    public function handle(): int
    {
        $offenders = User::withoutGlobalScopes()
            ->whereNotNull('organization_id')
            ->whereHas('roles', function ($query) {
                $query->where('name', 'super-admin')
                    ->orWhere('name', 'like', 'platform\_%');
            })
            ->with('roles:id,name')
            ->get(['id', 'name', 'email', 'organization_id']);

        if ($offenders->isEmpty()) {
            $this->info('✓ Không có user nào bị lệch scope (organization_id null) cho Platform Role.');

            return self::SUCCESS;
        }

        $this->error("Phát hiện {$offenders->count()} user có Platform Role nhưng organization_id KHÔNG null:");
        foreach ($offenders as $user) {
            $roles = $user->roles->pluck('name')->implode(', ');
            $this->line("  - #{$user->id} {$user->email} (organization_id={$user->organization_id}) — role: {$roles}");
        }

        return self::FAILURE;
    }
}
