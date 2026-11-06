<?php

namespace Database\Seeders;

use App\Enums\PermissionEnum as P;
use App\Enums\RoleEnum;
use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;

class RolePermissionSeeder extends Seeder
{
    public function run(): void
    {
        app()[PermissionRegistrar::class]->forgetCachedPermissions();

        $this->createAllPermissions();
        $this->createRolesWithPermissions();

        app()[PermissionRegistrar::class]->forgetCachedPermissions();
    }

    // ── Tạo toàn bộ permissions từ PermissionEnum ─────────────────────

    private function createAllPermissions(): void
    {
        foreach (P::cases() as $permission) {
            Permission::firstOrCreate([
                'name'       => $permission->value,
                'guard_name' => 'web',
            ]);
        }
    }

    // ── Map role → danh sách permission values ────────────────────────

    private function createRolesWithPermissions(): void
    {
        foreach ($this->rolePermissionMap() as $roleName => $permissions) {
            $role = Role::firstOrCreate([
                'name'       => $roleName,
                'guard_name' => 'web',
            ]);

            $role->syncPermissions($permissions);
        }
    }

    // ── Bảng phân quyền đầy đủ theo File 03 User Role Analysis ────────

    private function rolePermissionMap(): array
    {
        return [

            // ─────────────────────────────────────────────────────────
            // CEO / Founder — Full visibility, approve SOP
            // ─────────────────────────────────────────────────────────
            RoleEnum::CEO->value => [
                P::CEO_DASH_FULL->value,

                P::CUSTOMERS_VIEW_ALL->value,
                P::CUSTOMERS_CREATE->value,
                P::CUSTOMERS_EDIT->value,
                P::CUSTOMERS_DELETE->value,
                P::CUSTOMERS_EXPORT->value,

                P::SALES_AI_VIEW->value,

                P::WORKFLOW_MONITOR->value,

                P::USERS_VIEW->value,

                P::AUDIT_VIEW->value,
            ],

            // ─────────────────────────────────────────────────────────
            // Sales Team — Own tasks, AI assist
            // ─────────────────────────────────────────────────────────
            RoleEnum::SALES->value => [
                P::CUSTOMERS_VIEW_ASSIGNED->value,
                P::CUSTOMERS_CREATE->value,
                P::CUSTOMERS_EDIT->value,

                P::SALES_AI_USE->value,

                P::WORKFLOW_VIEW_LIMITED->value,
            ],

            // ─────────────────────────────────────────────────────────
            // Operations — Full task control, SOP authoring, workflow monitoring
            // ─────────────────────────────────────────────────────────
            RoleEnum::OPS->value => [
                P::CEO_DASH_VIEW->value,

                P::CUSTOMERS_VIEW_ALL->value,
                P::CUSTOMERS_CREATE->value,
                P::CUSTOMERS_EDIT->value,
                P::CUSTOMERS_EXPORT->value,

                P::WORKFLOW_MONITOR->value,
                P::WORKFLOW_EDIT->value,
            ],

            // ─────────────────────────────────────────────────────────
            // Marketing — Campaign tracking, limited tasks
            // ─────────────────────────────────────────────────────────
            RoleEnum::MARKETING->value => [
                P::CUSTOMERS_VIEW_ALL->value,

                P::SALES_AI_VIEW->value,

                P::WORKFLOW_VIEW_LIMITED->value,
            ],

            // ─────────────────────────────────────────────────────────
            // HR / Admin Staff — Onboarding, HR SOP, HR tasks
            // ─────────────────────────────────────────────────────────
            RoleEnum::HR->value => [
                P::WORKFLOW_VIEW_LIMITED->value,

                P::USERS_HR->value,
            ],

            // ─────────────────────────────────────────────────────────
            // AI Operator — Prompt management, AI logs, workflow AI config
            // ─────────────────────────────────────────────────────────
            RoleEnum::AI_OP->value => [
                P::CEO_DASH_VIEW->value,

                P::CUSTOMERS_VIEW_ALL->value,

                P::WORKFLOW_MONITOR->value,
                P::WORKFLOW_AI_CONFIG->value,
            ],

            // ─────────────────────────────────────────────────────────
            // System Admin — Full config access, no business data access
            // ─────────────────────────────────────────────────────────
            RoleEnum::ADMIN->value => [
                P::CEO_DASH_VIEW->value,
                P::CEO_DASH_CONFIG->value,

                P::CUSTOMERS_CONFIG->value,
                P::CUSTOMERS_VIEW_ALL->value,

                P::SALES_AI_CONFIG->value,

                P::WORKFLOW_MONITOR->value,
                P::WORKFLOW_EDIT->value,
                P::WORKFLOW_FULL_CONFIG->value,

                P::USERS_MANAGE->value,
                P::ROLES_MANAGE->value,

                P::INTEGRATION_MANAGE->value,
                P::AUDIT_VIEW->value,
                P::SYSTEM_CONFIG->value,

                // Menu: Full manage (điều hướng header/footer)
                P::MENU_MANAGE->value,
            ],

            // ─────────────────────────────────────────────────────────
            // Viewer / Partner — Read-only
            // ─────────────────────────────────────────────────────────
            RoleEnum::VIEWER->value => [
                P::CEO_DASH_VIEW->value,
            ],
        ];
    }
}
