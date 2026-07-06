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
            // CEO / Founder — Full visibility, approve SOP, manage leads
            // ─────────────────────────────────────────────────────────
            RoleEnum::CEO->value => [
                P::CEO_DASH_FULL->value,

                P::LEADS_VIEW_ALL->value,
                P::LEADS_CREATE->value,
                P::LEADS_EDIT->value,
                P::LEADS_DELETE->value,
                P::LEADS_ASSIGN->value,
                P::LEADS_EXPORT->value,

                P::CUSTOMERS_VIEW_ALL->value,
                P::CUSTOMERS_CREATE->value,
                P::CUSTOMERS_EDIT->value,
                P::CUSTOMERS_DELETE->value,
                P::CUSTOMERS_EXPORT->value,

                P::SALES_AI_VIEW->value,

                P::TASKS_VIEW_ALL->value,
                P::TASKS_CREATE->value,
                P::TASKS_EDIT->value,
                P::TASKS_ASSIGN->value,
                P::TASKS_CLOSE->value,

                P::SOP_VIEW->value,
                P::SOP_APPROVE->value,

                P::WORKFLOW_MONITOR->value,

                P::PROMPT_VIEW->value,

                P::AI_LOGS_VIEW->value,

                P::USERS_VIEW->value,

                P::REPORTS_FULL->value,

                P::AUDIT_VIEW->value,

                // Assessment: view + results
                P::ASSESSMENT_VIEW->value,
                P::ASSESSMENT_RESULTS->value,

                // Job Posting: full
                P::JOB_POSTING_VIEW->value,
                P::JOB_POSTING_CREATE->value,
                P::JOB_POSTING_EDIT->value,
                P::JOB_POSTING_DELETE->value,
                P::JOB_POSTING_PUBLISH->value,

                // Recruitment: view
                P::RECRUITMENT_VIEW->value,

                // Marketplace: view
                P::MARKETPLACE_VIEW->value,
            ],

            // ─────────────────────────────────────────────────────────
            // Sales Team — Assigned leads, own tasks, AI assist
            // ─────────────────────────────────────────────────────────
            RoleEnum::SALES->value => [
                P::LEADS_VIEW_ASSIGNED->value,
                P::LEADS_CREATE->value,
                P::LEADS_EDIT->value,

                P::CUSTOMERS_VIEW_ASSIGNED->value,
                P::CUSTOMERS_CREATE->value,
                P::CUSTOMERS_EDIT->value,

                P::SALES_AI_USE->value,

                P::TASKS_VIEW_ASSIGNED->value,
                P::TASKS_CREATE->value,

                P::SOP_VIEW_RELATED->value,

                P::WORKFLOW_VIEW_LIMITED->value,

                P::REPORTS_PERSONAL->value,
                P::REPORTS_TEAM->value,
            ],

            // ─────────────────────────────────────────────────────────
            // Operations — Full task control, SOP authoring, workflow monitoring
            // ─────────────────────────────────────────────────────────
            RoleEnum::OPS->value => [
                P::CEO_DASH_VIEW->value,

                P::LEADS_VIEW_ALL->value,
                P::LEADS_EXPORT->value,
                P::LEADS_MANAGE_TAGS->value,

                P::CUSTOMERS_VIEW_ALL->value,
                P::CUSTOMERS_CREATE->value,
                P::CUSTOMERS_EDIT->value,
                P::CUSTOMERS_EXPORT->value,

                P::TASKS_VIEW_ALL->value,
                P::TASKS_CREATE->value,
                P::TASKS_EDIT->value,
                P::TASKS_ASSIGN->value,
                P::TASKS_CLOSE->value,

                P::SOP_VIEW->value,
                P::SOP_CREATE->value,
                P::SOP_EDIT->value,

                P::WORKFLOW_MONITOR->value,
                P::WORKFLOW_EDIT->value,

                P::AI_LOGS_VIEW->value,

                P::REPORTS_OPS->value,

                // Assessment: view + results
                P::ASSESSMENT_VIEW->value,
                P::ASSESSMENT_RESULTS->value,
            ],

            // ─────────────────────────────────────────────────────────
            // Marketing — Source-view leads, campaign tracking, limited tasks
            // ─────────────────────────────────────────────────────────
            RoleEnum::MARKETING->value => [
                P::LEADS_VIEW_SOURCE->value,

                P::CUSTOMERS_VIEW_ALL->value,

                P::SALES_AI_VIEW->value,

                P::TASKS_VIEW_LIMITED->value,
                P::TASKS_CREATE->value,

                P::SOP_VIEW_RELATED->value,

                P::WORKFLOW_VIEW_LIMITED->value,

                P::REPORTS_MARKETING->value,

                // Marketplace: view (monitor public listings)
                P::MARKETPLACE_VIEW->value,
            ],

            // ─────────────────────────────────────────────────────────
            // HR / Admin Staff — Onboarding, HR SOP, HR tasks
            // ─────────────────────────────────────────────────────────
            RoleEnum::HR->value => [
                P::TASKS_VIEW_DEPT->value,
                P::TASKS_CREATE->value,

                P::SOP_VIEW->value,
                P::SOP_CREATE_HR->value,

                P::WORKFLOW_VIEW_LIMITED->value,

                P::USERS_HR->value,

                P::REPORTS_HR->value,

                // Job Posting: full
                P::JOB_POSTING_VIEW->value,
                P::JOB_POSTING_CREATE->value,
                P::JOB_POSTING_EDIT->value,
                P::JOB_POSTING_PUBLISH->value,

                // Recruitment: full
                P::RECRUITMENT_VIEW->value,
                P::RECRUITMENT_CREATE->value,
                P::RECRUITMENT_EDIT->value,
                P::RECRUITMENT_MANAGE->value,

                // Marketplace: full (manage listings + review applicants)
                P::MARKETPLACE_VIEW->value,
                P::MARKETPLACE_CREATE->value,
                P::MARKETPLACE_EDIT->value,
            ],

            // ─────────────────────────────────────────────────────────
            // AI Operator — Prompt management, AI logs, workflow AI config
            // ─────────────────────────────────────────────────────────
            RoleEnum::AI_OP->value => [
                P::CEO_DASH_VIEW->value,

                P::LEADS_VIEW_ALL->value,

                P::CUSTOMERS_VIEW_ALL->value,

                P::TASKS_VIEW_LIMITED->value,

                P::SOP_VIEW->value,
                P::SOP_AI_CONFIG->value,

                P::WORKFLOW_MONITOR->value,
                P::WORKFLOW_AI_CONFIG->value,

                P::PROMPT_FULL->value,

                P::AI_LOGS_FULL->value,

                P::REPORTS_AI_USAGE->value,

                // Assessment: full (config + reprocess)
                P::ASSESSMENT_VIEW->value,
                P::ASSESSMENT_CONFIG->value,
                P::ASSESSMENT_RESULTS->value,
                P::ASSESSMENT_REPROCESS->value,
            ],

            // ─────────────────────────────────────────────────────────
            // System Admin — Full config access, no business data access
            // ─────────────────────────────────────────────────────────
            RoleEnum::ADMIN->value => [
                P::CEO_DASH_VIEW->value,
                P::CEO_DASH_CONFIG->value,

                P::LEADS_CONFIG->value,
                P::LEADS_VIEW_ALL->value,
                P::LEADS_MANAGE_PIPELINE->value,
                P::LEADS_MANAGE_SOURCES->value,
                P::LEADS_MANAGE_TAGS->value,

                P::CUSTOMERS_CONFIG->value,
                P::CUSTOMERS_VIEW_ALL->value,

                P::SALES_AI_CONFIG->value,

                P::TASKS_CONFIG->value,

                P::SOP_CONFIG->value,

                P::WORKFLOW_MONITOR->value,
                P::WORKFLOW_EDIT->value,
                P::WORKFLOW_FULL_CONFIG->value,

                P::PROMPT_ADMIN_CONFIG->value,

                P::AI_LOGS_FULL->value,

                P::USERS_MANAGE->value,
                P::ROLES_MANAGE->value,

                P::REPORTS_FULL->value,

                P::INTEGRATION_MANAGE->value,
                P::AUDIT_VIEW->value,
                P::SYSTEM_CONFIG->value,

                // Assessment: full
                P::ASSESSMENT_VIEW->value,
                P::ASSESSMENT_CONFIG->value,
                P::ASSESSMENT_RESULTS->value,
                P::ASSESSMENT_REPROCESS->value,

                // Job Posting: full manage
                P::JOB_POSTING_VIEW->value,
                P::JOB_POSTING_CREATE->value,
                P::JOB_POSTING_EDIT->value,
                P::JOB_POSTING_DELETE->value,
                P::JOB_POSTING_PUBLISH->value,
                P::JOB_POSTING_MANAGE->value,

                // Recruitment: full manage
                P::RECRUITMENT_VIEW->value,
                P::RECRUITMENT_CREATE->value,
                P::RECRUITMENT_EDIT->value,
                P::RECRUITMENT_MANAGE->value,

                // Marketplace: full manage (approve orgs, global view)
                P::MARKETPLACE_VIEW->value,
                P::MARKETPLACE_CREATE->value,
                P::MARKETPLACE_EDIT->value,
                P::MARKETPLACE_MANAGE->value,

                // Vertical templates: full manage (dashboard/vertical-templates — thư viện mẫu)
                P::VERTICAL_TEMPLATES_MANAGE->value,
            ],

            // ─────────────────────────────────────────────────────────
            // Viewer / Partner — Read-only, shared reports
            // ─────────────────────────────────────────────────────────
            RoleEnum::VIEWER->value => [
                P::CEO_DASH_VIEW->value,
                P::TASKS_VIEW_LIMITED->value,
                P::SOP_VIEW->value,
                P::REPORTS_SHARED->value,
            ],
        ];
    }
}
