<?php

namespace Modules\Assessment\Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

/**
 * Kích hoạt feature module.assessment và phân quyền cho 12 demo employees.
 *
 * Cần chạy SAU WorkforceProfileSeeder (users đã tồn tại).
 */
class WorkforceRoleActivationSeeder extends Seeder
{
    public function run(): void
    {
        // ── 1. Enable module.assessment cho org #1 và org #2 ─────────────────
        $this->enableAssessmentFeature();

        // ── 2. Assign roles cho demo employees ───────────────────────────────
        $this->assignRoles();

        // ── 3. Xác minh email + nâng trust_level cho demo employees ──────────
        $this->verifyEmails();
    }

    // ─────────────────────────────────────────────────────────────────────────

    private function enableAssessmentFeature(): void
    {
        $orgIds = DB::table('organizations')->pluck('id');

        foreach ($orgIds as $orgId) {
            $exists = DB::table('organization_feature_overrides')
                ->where('organization_id', $orgId)
                ->where('feature_slug', 'module.assessment')
                ->exists();

            if (! $exists) {
                DB::table('organization_feature_overrides')->insert([
                    'uuid'            => \Illuminate\Support\Str::uuid(),
                    'order_column'    => $orgId,
                    'organization_id' => $orgId,
                    'feature_slug'    => 'module.assessment',
                    'value'           => '1',
                    'override_reason' => 'Demo seed — enable workforce module',
                    'expires_at'      => null,
                    'created_by'      => null,
                    'created_at'      => now(),
                    'updated_at'      => now(),
                ]);
            }
        }

        $this->command->info('  ✓ module.assessment enabled for ' . $orgIds->count() . ' org(s)');
    }

    private function assignRoles(): void
    {
        // Map: email pattern → role name (based on job title level in the seeder)
        // emp01: Intern (level 1) → member
        // emp02-03: Staff (level ~5) → member
        // emp04-06: Specialist (level 6-8) → ops
        // emp07-09: Sr Specialist / Team Lead / Manager (level 8-14) → ops / manager
        // emp10-11: Sr Manager / Director (level 15-17) → manager
        // emp12: CEO (level 20) → ceo
        $roleMap = [
            'demo.emp01@thuchoc.vn' => 'member',
            'demo.emp02@thuchoc.vn' => 'member',
            'demo.emp03@thuchoc.vn' => 'member',
            'demo.emp04@thuchoc.vn' => 'ops',
            'demo.emp05@thuchoc.vn' => 'ops',
            'demo.emp06@thuchoc.vn' => 'ops',
            'demo.emp07@thuchoc.vn' => 'ops',
            'demo.emp08@thuchoc.vn' => 'manager',
            'demo.emp09@thuchoc.vn' => 'manager',
            'demo.emp10@thuchoc.vn' => 'manager',
            'demo.emp11@thuchoc.vn' => 'ceo',
            'demo.emp12@thuchoc.vn' => 'ceo',
        ];

        $roles = DB::table('roles')->where('guard_name', 'web')->pluck('id', 'name');
        $assigned = 0;

        foreach ($roleMap as $email => $roleName) {
            $user = DB::table('users')->where('email', $email)->first(['id']);
            if (! $user) continue;

            $roleId = $roles[$roleName] ?? null;
            if (! $roleId) {
                $this->command->warn("  ! Role '{$roleName}' not found, skipping {$email}");
                continue;
            }

            // Determine org_id from the employee record
            $emp = DB::table('employees')->where('user_id', $user->id)->first(['organization_id']);
            $orgId = $emp?->organization_id ?? 1;

            $alreadyHas = DB::table('model_has_roles')
                ->where('role_id', $roleId)
                ->where('model_type', 'App\\Models\\User')
                ->where('model_id', $user->id)
                ->where('organization_id', $orgId)
                ->exists();

            if (! $alreadyHas) {
                DB::table('model_has_roles')->insert([
                    'role_id'         => $roleId,
                    'model_type'      => 'App\\Models\\User',
                    'model_id'        => $user->id,
                    'organization_id' => $orgId,
                ]);
                $assigned++;
            }
        }

        $this->command->info("  ✓ Assigned roles to {$assigned} demo employee(s)");
        $this->command->table(
            ['Email', 'Role', 'Access level'],
            collect($roleMap)->map(fn ($role, $email) => [
                $email,
                $role,
                match ($role) {
                    'member'  => 'Personal dashboard /me only',
                    'ops'     => '/me + view all profiles + export',
                    'manager' => '/me + full admin: index/show/export/pdf',
                    'ceo'     => '/me + full admin + all reports',
                    default   => '—',
                },
            ])->values()->toArray()
        );
    }

    private function verifyEmails(): void
    {
        // Tất cả 12 demo employees đều được xác minh email (trust_level >= 1)
        $emails = [
            'demo.emp01@thuchoc.vn',
            'demo.emp02@thuchoc.vn',
            'demo.emp03@thuchoc.vn',
            'demo.emp04@thuchoc.vn',
            'demo.emp05@thuchoc.vn',
            'demo.emp06@thuchoc.vn',
            'demo.emp07@thuchoc.vn',
            'demo.emp08@thuchoc.vn',
            'demo.emp09@thuchoc.vn',
            'demo.emp10@thuchoc.vn',
            'demo.emp11@thuchoc.vn',
            'demo.emp12@thuchoc.vn',
        ];

        $verifiedAt = now();
        $updated = 0;

        foreach ($emails as $email) {
            $rows = DB::table('users')
                ->where('email', $email)
                ->where(function ($q) {
                    $q->whereNull('email_verified_at')
                      ->orWhere('trust_level', '<', 1);
                })
                ->update([
                    'email_verified_at' => DB::raw('COALESCE(email_verified_at, \'' . $verifiedAt . '\')'),
                    'trust_level'       => DB::raw('CASE WHEN trust_level < 1 THEN 1 ELSE trust_level END'),
                    'updated_at'        => $verifiedAt,
                ]);

            $updated += $rows;
        }

        $this->command->info("  ✓ Email verified + trust_level ≥ 1 for {$updated} demo user(s)");
    }
}
