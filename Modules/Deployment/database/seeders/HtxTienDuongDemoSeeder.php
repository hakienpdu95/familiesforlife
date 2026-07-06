<?php

namespace Modules\Deployment\Database\Seeders;

use App\Enums\RoleEnum;
use App\Models\User;
use App\Shared\Tenancy\Models\Organization;
use App\Shared\Tenancy\TenantContext;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use Modules\AiCopilot\Models\AiAgent;
use Modules\AiCopilot\Models\AiPrompt;
use Modules\BusinessBlueprint\Models\Blueprint;
use Modules\Deployment\Actions\DeployOrganizationSolutionAction;
use Modules\OrganizationSolution\Features\SolutionActivation\Actions\ActivateBusinessSolutionAction;
use Modules\OrganizationSolution\Features\SolutionActivation\Actions\ConfigureAiAction;
use Modules\OrganizationSolution\Features\SolutionActivation\Actions\ConfigureCapabilitiesAction;
use Modules\OrganizationSolution\Features\SolutionActivation\Actions\ConfigureChecklistsAction;
use Modules\OrganizationSolution\Features\SolutionActivation\Actions\ConfigureDashboardAction;
use Modules\OrganizationSolution\Features\SolutionActivation\Actions\ConfigureResourcesAction;
use Modules\OrganizationSolution\Features\SolutionActivation\Actions\ConfigureWorkflowsAction;
use Modules\OrganizationSolution\Features\SolutionActivation\Actions\MapRolesAction;
use Modules\OrganizationSolution\Features\SolutionActivation\Actions\MarkSolutionReadyAction;
use Modules\OrganizationSolution\Features\SolutionActivation\Data\ActivateBusinessSolutionData;
use Modules\OrganizationSolution\Features\SolutionActivation\Queries\ValidatePreDeployHandler;
use Modules\OrganizationSolution\Models\OrganizationSolution;
use Modules\Subscription\Features\Subscribe\Actions\SubscribeOrganizationAction;

/**
 * Kích hoạt + cấu hình (wizard 8 bước, A07 §3) + Deploy Blueprint "BP-TXNG" cho
 * tổ chức mẫu "HTX Tiên Dương" — đúng ví dụ minh họa xuyên suốt tài liệu (§3.3):
 * HTX Tiên Dương triển khai AI Truy xuất nguồn gốc, thêm biểu mẫu BM-01 → BM-01-HTX,
 * Role Mapping Field Officer/Supervisor/Manager → nhân sự thật của HTX.
 *
 * Chạy SAU TxngBlueprintSeeder (cần blueprint BP-TXNG đã published).
 * Idempotent: nếu HTX Tiên Dương đã có Organization Solution thì bỏ qua toàn bộ.
 */
class HtxTienDuongDemoSeeder extends Seeder
{
    public function run(): void
    {
        $org = Organization::firstOrCreate(
            ['slug' => 'htx-tien-duong'],
            [
                'name'     => 'HTX Tiên Dương',
                'status'   => 'active',
                'settings' => ['timezone' => 'Asia/Ho_Chi_Minh', 'locale' => 'vi'],
            ]
        );

        if (OrganizationSolution::withoutTenant()->where('organization_id', $org->id)->exists()) {
            $this->command?->info('  ⏭ HTX Tiên Dương đã có Organization Solution — bỏ qua HtxTienDuongDemoSeeder.');

            return;
        }

        // Org được tạo trực tiếp qua Organization::firstOrCreate() (không qua action/event
        // OrganizationCreated) — AutoSubscribeOnOrgCreated listener không tự chạy, nên gán
        // plan mặc định thủ công. Thiếu bước này, CheckSubscription middleware sẽ redirect
        // mọi request của org này sang /billing.
        if (! $org->planSubscription('main')?->active()) {
            SubscribeOrganizationAction::subscribeToDefaultPlan($org);
        }

        // Role Spatie cấp platform (khác Role Mapping trừu tượng field_officer/supervisor/
        // manager của Blueprint) — giamdoc=CEO (chủ tổ chức), totruong/nhanvien=Ops (vận hành).
        $giamDoc  = $this->user($org, 'giamdoc@htx-tien-duong.test', 'Nguyễn Văn An', RoleEnum::CEO);
        $toTruong = $this->user($org, 'totruong@htx-tien-duong.test', 'Trần Thị Bình', RoleEnum::OPS);
        $nhanVien = $this->user($org, 'nhanvien@htx-tien-duong.test', 'Lê Văn Cường', RoleEnum::OPS);

        if (! $org->owner_id) {
            $org->update(['owner_id' => $giamDoc->id]);
        }

        // CreateVerticalProjectAction gán Project.owner_id = Employee.id (không phải
        // users.id) — cả 3 nhân sự HTX cần có hồ sơ Employee thật để có thể làm chủ
        // dự án / được gán checklist.
        [$branchId, $departmentId] = $this->ensureBranchAndDepartment($org);
        $this->ensureEmployee($org, $giamDoc, $branchId, $departmentId, 'HTX-001');
        $this->ensureEmployee($org, $toTruong, $branchId, $departmentId, 'HTX-002');
        $this->ensureEmployee($org, $nhanVien, $branchId, $departmentId, 'HTX-003');

        $blueprint = Blueprint::where('code', 'BP-TXNG')->firstOrFail();
        $version   = $blueprint->currentVersion; // version published bởi TxngBlueprintSeeder

        // AI Agent/Prompt gắn ở tầng AI Configuration của TỔ CHỨC (không phải Blueprint,
        // DP-06 §5.4) — org-scoped (organization_id = HTX) để findOrFail() trong
        // ConfigureAiAction/ValidatePreDeployHandler resolve đúng dưới TenantContext hiện tại.
        [$aiAgent, $aiPrompt] = $this->seedAiAgent($org, $giamDoc);

        $previousUser = Auth::user();
        Auth::onceUsingId($giamDoc->id);

        try {
            TenantContext::runForOrganization(
                $org,
                function () use ($org, $giamDoc, $toTruong, $nhanVien, $blueprint, $version, $aiAgent, $aiPrompt): void {
                    $orgSolution = (new ActivateBusinessSolutionAction())->handle(ActivateBusinessSolutionData::from([
                        'business_solution_id' => $blueprint->business_solution_id,
                        'blueprint_version_id'  => $version->id,
                        'name'                  => 'AI Truy xuất nguồn gốc — HTX Tiên Dương',
                        'owner_id'              => $giamDoc->id,
                    ]));

                    $version->loadMissing([
                        'capabilities.workflows.phases.checklists', 'resourceLinks',
                        'deploymentRoles', 'aiCapabilities', 'analytics',
                    ]);

                    // Bước 3 — Capability Configuration: bật toàn bộ Capability.
                    (new ConfigureCapabilitiesAction())->handle($orgSolution, $version->capabilities
                        ->map(fn ($c) => ['blueprint_capability_id' => $c->id, 'enabled' => true])
                        ->all());

                    // Bước 4 — Workflow Configuration: bật Workflow, SLA 30 ngày, chủ trì = Tổ trưởng vùng.
                    $workflow = $version->capabilities->firstWhere('code', 'CAP-VUNGTRONG')->workflows->first();
                    (new ConfigureWorkflowsAction())->handle($orgSolution, [[
                        'blueprint_workflow_id' => $workflow->id, 'enabled' => true,
                        'default_owner_id'       => $toTruong->id, 'sla_days' => 30,
                    ]]);

                    // Bước 4b — Checklist Configuration: bật toàn bộ checklist, gán mặc định.
                    $checklists = $workflow->phases->flatMap->checklists;
                    (new ConfigureChecklistsAction())->handle($orgSolution, $checklists->map(fn ($cl) => [
                        'blueprint_checklist_id' => $cl->id, 'enabled' => true,
                        'default_assignee_id'     => $nhanVien->id, 'default_reviewer_id' => $toTruong->id,
                        'due_days'                => 5,
                    ])->all());

                    // Bước 5 — Resource Configuration: BM-01 (blueprint gốc) → BM-01-HTX (ví dụ §10.4).
                    $bm01Link = $version->resourceLinks->firstWhere('resource_type', 'knowledge');
                    (new ConfigureResourcesAction())->handle($orgSolution, [[
                        'blueprint_resource_link_id' => $bm01Link->id, 'override_reference' => 'BM-01-HTX',
                    ]]);

                    // Bước 6 — AI Configuration: bật AI Validation dùng agent/prompt riêng của HTX.
                    $aiCapability = $version->aiCapabilities->first();
                    (new ConfigureAiAction())->handle($orgSolution, [[
                        'ai_capability_code' => $aiCapability->capability_code, 'enabled' => true,
                        'ai_agent_id'         => $aiAgent->id, 'ai_prompt_id' => $aiPrompt->id,
                        'provider'            => 'claude',
                    ]]);

                    // Bước 7 — Dashboard Configuration: 1 widget / metric đã khai báo ở Blueprint.
                    (new ConfigureDashboardAction())->handle($orgSolution, $version->analytics->map(fn ($metric) => [
                        'blueprint_analytic_id' => $metric->id, 'widget_type' => 'metric',
                        'title'                  => $metric->name, 'enabled' => true,
                    ])->all());

                    // Role Mapping (A07 §12): Field Officer/Supervisor/Manager → nhân sự thật của HTX.
                    $roleUserMap = [
                        'field_officer' => $nhanVien->id,
                        'supervisor'    => $toTruong->id,
                        'manager'       => $giamDoc->id,
                    ];
                    (new MapRolesAction())->handle($orgSolution, $version->deploymentRoles->map(fn ($role) => [
                        'blueprint_role_code' => $role->role_code,
                        'user_id'              => $roleUserMap[$role->role_code] ?? null,
                        'mapping_type'         => 'user',
                    ])->all());

                    // Bước 8 — Review: 7 điều kiện Pre-Deploy (A07 §14) → draft/configuring → ready.
                    $orgSolution = (new MarkSolutionReadyAction(app(ValidatePreDeployHandler::class)))
                        ->handle($orgSolution->fresh());

                    // Deploy: sinh Project/Workflow/Phase/Checklist Runtime + Deployment Log/Snapshot.
                    $deployment = (new DeployOrganizationSolutionAction())->handle($orgSolution->fresh());

                    $this->command?->info(sprintf(
                        '  ✓ HTX Tiên Dương: Organization Solution #%d deployed — Deployment #%d, Project #%d.',
                        $orgSolution->id,
                        $deployment->id,
                        $deployment->project_id,
                    ));
                }
            );
        } finally {
            if ($previousUser) {
                Auth::setUser($previousUser);
            } else {
                Auth::forgetGuards();
            }
        }
    }

    private function user(Organization $org, string $email, string $name, RoleEnum $role): User
    {
        $user = User::firstOrCreate(
            ['email' => $email],
            [
                'name'            => $name,
                'password'        => Hash::make('password'),
                'organization_id' => $org->id,
            ]
        );

        // email_verified_at không nằm trong #[Fillable(...)] của App\Models\User —
        // set trực tiếp để tài khoản demo đăng nhập được ngay, không cần xác thực email.
        if (! $user->email_verified_at) {
            $user->forceFill(['email_verified_at' => now()])->save();
        }

        // Spatie role scoped theo team = organization (cùng convention với UserSeeder) —
        // quyết định sidebar/permission hiển thị (config/permissions.php), KHÔNG ảnh hưởng
        // gì tới các route Deployment vận hành (dashboard/projects/checklist — không gate quyền).
        setPermissionsTeamId($org->id);
        $user->syncRoles([$role->value]);
        setPermissionsTeamId(null);

        return $user;
    }

    /** @return array{0: int, 1: int} [branch_id, department_id] */
    private function ensureBranchAndDepartment(Organization $org): array
    {
        $branchId = DB::table('branches')->where('organization_id', $org->id)->value('id');
        if (! $branchId) {
            $branchId = DB::table('branches')->insertGetId([
                'uuid'            => (string) Str::uuid(),
                'organization_id' => $org->id,
                'name'            => 'Trụ sở chính',
                'code'            => 'HQ',
                'type'            => 'headquarters',
                'status'          => 'active',
                'created_at'      => now(),
                'updated_at'      => now(),
            ]);
        }

        $departmentId = DB::table('departments')->where('organization_id', $org->id)->value('id');
        if (! $departmentId) {
            $departmentId = DB::table('departments')->insertGetId([
                'uuid'            => (string) Str::uuid(),
                'organization_id' => $org->id,
                'branch_id'       => $branchId,
                'name'            => 'Ban điều hành',
                'code'            => 'BDH',
                'function'        => 'operations',
                'status'          => 'active',
                'created_at'      => now(),
                'updated_at'      => now(),
            ]);
        }

        return [$branchId, $departmentId];
    }

    /** DB::table insert trực tiếp (bỏ qua LogsActivity observer — cùng convention với WorkforceProfileSeeder). */
    private function ensureEmployee(Organization $org, User $user, int $branchId, int $departmentId, string $code): void
    {
        $exists = DB::table('employees')
            ->where('organization_id', $org->id)->where('user_id', $user->id)->exists();

        if ($exists) {
            return;
        }

        DB::table('employees')->insert([
            'uuid'            => (string) Str::uuid(),
            'organization_id' => $org->id,
            'user_id'         => $user->id,
            'branch_id'       => $branchId,
            'department_id'   => $departmentId,
            'employee_code'   => $code,
            'full_name'       => $user->name,
            'email'           => $user->email,
            'status'          => 'active',
            'employment_type' => 'full_time',
            'created_at'      => now(),
            'updated_at'      => now(),
        ]);
    }

    /** @return array{0: AiAgent, 1: AiPrompt} */
    private function seedAiAgent(Organization $org, User $createdBy): array
    {
        $agent = AiAgent::withoutTenant()->withoutGlobalScope('active')->updateOrCreate(
            ['slug' => 'txng.document_validation', 'organization_id' => $org->id],
            [
                'uuid'            => (string) Str::uuid(),
                'name'            => 'TXNG Document Validator — HTX Tiên Dương',
                'description'     => 'Kiểm tra tính đầy đủ hồ sơ truy xuất nguồn gốc nông sản (OCR + đối chiếu checklist).',
                'task_type'       => 'custom',
                'provider'        => 'claude',
                'model'           => 'claude-sonnet-4-6',
                'temperature'     => 0.2,
                'max_tokens'      => 2048,
                'timeout_seconds' => 30,
                'sync_mode'       => false,
                'is_active'       => true,
                'is_system'       => false,
                'created_by'      => $createdBy->id,
            ]
        );

        $prompt = AiPrompt::withoutTenant()->updateOrCreate(
            ['agent_id' => $agent->id, 'organization_id' => $org->id, 'is_default' => true],
            [
                'uuid'          => (string) Str::uuid(),
                'name'          => 'Kiểm tra tính đầy đủ hồ sơ TXNG',
                'description'   => 'Đối chiếu hồ sơ đã thu thập với checklist bắt buộc của phase Kiểm tra.',
                'system_prompt' => 'Bạn là trợ lý kiểm tra hồ sơ truy xuất nguồn gốc nông sản. Đối chiếu tài '
                    . 'liệu được cung cấp với danh sách checklist bắt buộc, chỉ ra tài liệu còn thiếu hoặc không hợp lệ.',
                'user_template' => 'Danh sách checklist bắt buộc: {{checklist_items}}. Danh sách tài liệu đã '
                    . 'nộp: {{submitted_files}}. Hãy liệt kê phần còn thiếu.',
                'is_active'     => true,
                'version'       => 1,
                'created_by'    => $createdBy->id,
            ]
        );

        return [$agent, $prompt];
    }
}
