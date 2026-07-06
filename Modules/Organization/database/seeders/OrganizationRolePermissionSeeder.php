<?php

namespace Modules\Organization\Database\Seeders;

use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;

/**
 * Create organization-scoped template roles and assign permissions.
 *
 * These roles are created with organization_id = null as TEMPLATES.
 * When a new organization is created, concrete instances scoped to that
 * organization's team_id will be created for actual assignment.
 *
 * Roles:
 *  owner   — all permissions (org-scoped super-admin)
 *  admin   — most permissions except system config
 *  manager — create/edit most things, view all
 *  member  — view own, create leads/tasks
 */
class OrganizationRolePermissionSeeder extends Seeder
{
    public function run(): void
    {
        // Use null team so these are template roles, not org-scoped
        setPermissionsTeamId(null);
        app(PermissionRegistrar::class)->forgetCachedPermissions();

        $allPermissions = Permission::all();

        // ── owner: all permissions ────────────────────────────────────────
        $owner = Role::firstOrCreate([
            'name'            => 'owner',
            'guard_name'      => 'web',
            'organization_id' => null,
        ]);
        $owner->syncPermissions($allPermissions);

        // ── admin: most permissions, no system config ─────────────────────
        $adminPermissionNames = $allPermissions
            ->filter(fn (Permission $p) => ! in_array($p->name, ['system.config', 'system_config'], true))
            ->pluck('name')
            ->all();

        $admin = Role::firstOrCreate([
            'name'            => 'admin',
            'guard_name'      => 'web',
            'organization_id' => null,
        ]);
        $admin->syncPermissions($adminPermissionNames);

        // ── manager: view all + create/edit most things ───────────────────
        $managerPermissionNames = $allPermissions
            ->filter(function (Permission $p): bool {
                // Exclude: delete, config, system, roles management, audit
                $blocklist = ['delete', 'config', 'system', 'roles.manage', 'audit', 'integration'];
                foreach ($blocklist as $term) {
                    if (str_contains($p->name, $term)) {
                        return false;
                    }
                }

                return true;
            })
            ->pluck('name')
            ->all();

        $manager = Role::firstOrCreate([
            'name'            => 'manager',
            'guard_name'      => 'web',
            'organization_id' => null,
        ]);
        $manager->syncPermissions($managerPermissionNames);

        // ── member: view own, create leads/tasks ──────────────────────────
        $memberPermissions = [
            'leads.view_assigned',
            'leads.create',
            'tasks.view_assigned',
            'tasks.create',
            'sop.view_related',
        ];

        $existingMemberPerms = $allPermissions
            ->whereIn('name', $memberPermissions)
            ->pluck('name')
            ->all();

        $member = Role::firstOrCreate([
            'name'            => 'member',
            'guard_name'      => 'web',
            'organization_id' => null,
        ]);
        $member->syncPermissions($existingMemberPerms);

        app(PermissionRegistrar::class)->forgetCachedPermissions();

        $this->command->info('  ✓ Organization template roles seeded: owner, admin, manager, member.');
    }
}
