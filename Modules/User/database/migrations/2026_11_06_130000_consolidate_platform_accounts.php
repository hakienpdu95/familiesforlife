<?php

use App\Models\User;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Spatie\Permission\PermissionRegistrar;

return new class extends Migration
{
    private const ORGANIZATION_ROLES = [
        'ceo', 'sales', 'ops', 'marketing', 'hr', 'ai_operator', 'system_admin', 'viewer',
        'owner', 'admin', 'manager', 'member',
    ];

    private const ORPHAN_PERMISSION_PREFIXES = [
        'ceo_dashboard.', 'sales_ai.', 'workflow.', 'users.', 'roles.', 'integration.',
        'audit.', 'system.', 'customers.',
    ];

    public function up(): void
    {
        $this->deleteDemoAccounts();

        Schema::dropIfExists('member_post_exit_audits');
        Schema::dropIfExists('organization_members');

        $this->deleteOrganizationRoles();
        $this->deleteOrphanPermissions();

        app(PermissionRegistrar::class)->forgetCachedPermissions();
    }

    public function down(): void {}

    private function deleteDemoAccounts(): void
    {
        if (! Schema::hasTable('organizations') || ! Schema::hasColumn('users', 'organization_id')) {
            return;
        }

        $demoOrgId = DB::table('organizations')->where('slug', 'demo')->value('id');

        if ($demoOrgId === null) {
            return;
        }

        $ids = DB::table('users')
            ->where('organization_id', $demoOrgId)
            ->where('email', 'like', '%@demo.test')
            ->pluck('id');

        if ($ids->isEmpty()) {
            return;
        }

        $emails = DB::table('users')->whereIn('id', $ids)->pluck('email');

        DB::transaction(function () use ($ids, $emails, $demoOrgId): void {
            DB::table('model_has_roles')->where('model_type', User::class)->whereIn('model_id', $ids)->delete();
            DB::table('model_has_permissions')->where('model_type', User::class)->whereIn('model_id', $ids)->delete();

            if (Schema::hasTable('notifications')) {
                DB::table('notifications')->where('notifiable_type', User::class)->whereIn('notifiable_id', $ids)->delete();
            }

            if (Schema::hasTable('personal_access_tokens')) {
                DB::table('personal_access_tokens')->where('tokenable_type', User::class)->whereIn('tokenable_id', $ids)->delete();
            }

            if (Schema::hasTable('sessions')) {
                DB::table('sessions')->whereIn('user_id', $ids)->delete();
            }

            if (Schema::hasTable('password_reset_tokens')) {
                DB::table('password_reset_tokens')->whereIn('email', $emails)->delete();
            }

            if (Schema::hasTable('organization_members')) {
                DB::table('organization_members')->whereIn('user_id', $ids)->delete();
            }

            DB::table('organizations')->where('id', $demoOrgId)->whereIn('owner_id', $ids)->update(['owner_id' => null]);

            DB::table('users')->whereIn('id', $ids)->delete();
        });
    }

    private function deleteOrganizationRoles(): void
    {
        $roleIds = DB::table('roles')->whereIn('name', self::ORGANIZATION_ROLES)->pluck('id');

        if ($roleIds->isEmpty()) {
            return;
        }

        DB::transaction(function () use ($roleIds): void {
            DB::table('role_has_permissions')->whereIn('role_id', $roleIds)->delete();
            DB::table('model_has_roles')->whereIn('role_id', $roleIds)->delete();
            DB::table('roles')->whereIn('id', $roleIds)->delete();
        });
    }

    private function deleteOrphanPermissions(): void
    {
        $permissionIds = DB::table('permissions')
            ->where(function ($q): void {
                foreach (self::ORPHAN_PERMISSION_PREFIXES as $prefix) {
                    $q->orWhere('name', 'like', $prefix.'%');
                }
            })
            ->pluck('id');

        if ($permissionIds->isEmpty()) {
            return;
        }

        DB::transaction(function () use ($permissionIds): void {
            DB::table('role_has_permissions')->whereIn('permission_id', $permissionIds)->delete();
            DB::table('model_has_permissions')->whereIn('permission_id', $permissionIds)->delete();
            DB::table('permissions')->whereIn('id', $permissionIds)->delete();
        });
    }
};
