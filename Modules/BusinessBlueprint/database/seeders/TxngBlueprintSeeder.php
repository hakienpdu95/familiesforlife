<?php

namespace Modules\BusinessBlueprint\Database\Seeders;

use App\Models\User;
use App\Shared\Tenancy\Models\Organization;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;
use Modules\BusinessBlueprint\Enums\BlueprintVersionStatus;
use Modules\BusinessBlueprint\Features\BlueprintAuthoring\Actions\CreateBlueprintAction;
use Modules\BusinessBlueprint\Features\BlueprintAuthoring\Actions\PublishBlueprintVersionAction;
use Modules\BusinessBlueprint\Features\BlueprintAuthoring\Actions\UpsertAiCapabilityAction;
use Modules\BusinessBlueprint\Features\BlueprintAuthoring\Actions\UpsertAnalyticAction;
use Modules\BusinessBlueprint\Features\BlueprintAuthoring\Actions\UpsertCapabilityAction;
use Modules\BusinessBlueprint\Features\BlueprintAuthoring\Actions\UpsertChecklistAction;
use Modules\BusinessBlueprint\Features\BlueprintAuthoring\Actions\UpsertDeploymentRoleAction;
use Modules\BusinessBlueprint\Features\BlueprintAuthoring\Actions\UpsertOutcomeAction;
use Modules\BusinessBlueprint\Features\BlueprintAuthoring\Actions\UpsertPhaseAction;
use Modules\BusinessBlueprint\Features\BlueprintAuthoring\Actions\UpsertResourceLinkAction;
use Modules\BusinessBlueprint\Features\BlueprintAuthoring\Actions\UpsertSidebarItemAction;
use Modules\BusinessBlueprint\Features\BlueprintAuthoring\Actions\UpsertWorkflowAction;
use Modules\BusinessBlueprint\Features\BlueprintAuthoring\Data\AiCapabilityData;
use Modules\BusinessBlueprint\Features\BlueprintAuthoring\Data\AnalyticData;
use Modules\BusinessBlueprint\Features\BlueprintAuthoring\Data\BlueprintData;
use Modules\BusinessBlueprint\Features\BlueprintAuthoring\Data\CapabilityData;
use Modules\BusinessBlueprint\Features\BlueprintAuthoring\Data\ChecklistData;
use Modules\BusinessBlueprint\Features\BlueprintAuthoring\Data\DeploymentRoleData;
use Modules\BusinessBlueprint\Features\BlueprintAuthoring\Data\OutcomeData;
use Modules\BusinessBlueprint\Features\BlueprintAuthoring\Data\PhaseData;
use Modules\BusinessBlueprint\Features\BlueprintAuthoring\Data\ResourceLinkData;
use Modules\BusinessBlueprint\Features\BlueprintAuthoring\Data\SidebarItemData;
use Modules\BusinessBlueprint\Features\BlueprintAuthoring\Data\WorkflowData;
use Modules\BusinessBlueprint\Features\BlueprintAuthoring\Queries\ValidateBlueprintIntegrityHandler;
use Modules\BusinessBlueprint\Features\BlueprintAuthoring\Queries\ValidateBlueprintReadinessHandler;
use Modules\BusinessBlueprint\Models\Blueprint;
use Modules\BusinessSolution\Models\BusinessSolution;
use Modules\KcCategory\Models\KcCategory;
use Modules\KcItem\Models\KcItem;
use Modules\Sop\Enums\SopStatus;
use Modules\Sop\Enums\SopType;
use Modules\Sop\Models\SopProcess;

/**
 * Dựng Blueprint "BP-TXNG" đầy đủ 8 thành phần (Overview/Outcomes/Capabilities/
 * Process/Resources/AI/Analytics/Deployment Settings) cho Business Solution
 * "AI Truy xuất nguồn gốc" (đã seed ở BusinessSolutionCatalogSeeder), đúng ví dụ
 * xuyên suốt của tài liệu (docs/thuchoc §3.3, §9.6): Khảo sát → Thu thập hồ sơ →
 * Chuẩn hóa → Kiểm tra (AI Validation) → Bàn giao.
 *
 * Đây là Definition Data cấp hệ thống (Blueprint không thuộc 1 Organization) —
 * các Resource (SOP/Form) tham chiếu được gắn vào Organization "system" (is_system=true),
 * đúng vai trò của org này (dữ liệu không thuộc riêng doanh nghiệp nào — xem
 * SystemOrganizationSeeder). AI Agent/Prompt KHÔNG được gắn ở tầng Blueprint (DP-06,
 * §5.4) — việc đó thuộc AI Configuration cấp Organization, xem HtxTienDuongDemoSeeder.
 *
 * Idempotent: nếu blueprint code "BP-TXNG" đã tồn tại thì bỏ qua toàn bộ.
 */
class TxngBlueprintSeeder extends Seeder
{
    public function run(): void
    {
        if (Blueprint::where('code', 'BP-TXNG')->exists()) {
            $this->command?->info('  ⏭ BP-TXNG đã tồn tại — bỏ qua TxngBlueprintSeeder.');

            return;
        }

        $solution    = BusinessSolution::where('code', 'AI-TXNG')->firstOrFail();
        $systemOrg   = Organization::where('slug', 'system')->firstOrFail();
        $systemUser  = User::where('email', 'admin@demo.test')->firstOrFail();

        [$sopSurvey, $sopStandardize] = $this->seedSopResources($systemOrg, $systemUser);
        $bm01Form                     = $this->seedFormResource($systemOrg, $systemUser);

        $blueprint = (new CreateBlueprintAction())->handle(BlueprintData::from([
            'business_solution_id' => $solution->id,
            'code'                  => 'BP-TXNG',
            'name'                  => 'Blueprint Truy xuất nguồn gốc nông sản — Cơ bản',
            'description'           => 'Bản thiết kế nghiệp vụ chuẩn cho AI Truy xuất nguồn gốc: khảo '
                . 'sát vùng trồng, thu thập & chuẩn hóa hồ sơ, AI kiểm tra tính đầy đủ, sinh mã QR và '
                . 'bàn giao hồ sơ điện tử cho đối tác thu mua.',
        ]), $systemUser->id);

        $version = $blueprint->currentVersion;

        // ── Business Outcomes (§5.4) ──────────────────────────────────────
        $outHoso = (new UpsertOutcomeAction())->handle(OutcomeData::from([
            'blueprint_version_id' => $version->id, 'code' => 'OUT-HOSO',
            'name'                  => 'Hồ sơ vùng trồng đầy đủ', 'sort_order' => 0,
            'success_metric'        => '100% lô sản xuất có hồ sơ đạt yêu cầu tối thiểu',
        ]));
        $outQr = (new UpsertOutcomeAction())->handle(OutcomeData::from([
            'blueprint_version_id' => $version->id, 'code' => 'OUT-QR',
            'name'                  => 'Mã QR truy xuất hoạt động', 'sort_order' => 1,
            'success_metric'        => 'QR quét ra đúng thông tin lô, không lỗi 404',
        ]));
        $outBangiao = (new UpsertOutcomeAction())->handle(OutcomeData::from([
            'blueprint_version_id' => $version->id, 'code' => 'OUT-BANGIAO',
            'name'                  => 'Bàn giao hồ sơ đúng hạn cho đối tác thu mua', 'sort_order' => 2,
            'success_metric'        => 'Bàn giao trước hạn hợp đồng thu mua',
        ]));

        // ── Business Capabilities (§5.4) ──────────────────────────────────
        $capVungTrong = (new UpsertCapabilityAction())->handle(CapabilityData::from([
            'blueprint_version_id' => $version->id, 'outcome_id' => $outHoso->id,
            'code' => 'CAP-VUNGTRONG', 'name' => 'Quản lý vùng trồng', 'sort_order' => 0,
        ]));
        (new UpsertCapabilityAction())->handle(CapabilityData::from([
            'blueprint_version_id' => $version->id, 'outcome_id' => $outHoso->id,
            'code' => 'CAP-LO', 'name' => 'Quản lý lô sản xuất', 'sort_order' => 1,
        ]));
        (new UpsertCapabilityAction())->handle(CapabilityData::from([
            'blueprint_version_id' => $version->id, 'outcome_id' => $outHoso->id,
            'code' => 'CAP-AIVALIDATION', 'name' => 'AI kiểm tra tính đầy đủ hồ sơ',
            'capability_type' => 'ai', 'sort_order' => 2,
        ]));
        (new UpsertCapabilityAction())->handle(CapabilityData::from([
            'blueprint_version_id' => $version->id, 'outcome_id' => $outQr->id,
            'code' => 'CAP-QR', 'name' => 'Quản lý mã QR truy xuất', 'sort_order' => 3,
        ]));
        (new UpsertCapabilityAction())->handle(CapabilityData::from([
            'blueprint_version_id' => $version->id, 'outcome_id' => $outBangiao->id,
            'code' => 'CAP-BANGIAO', 'name' => 'Bàn giao hồ sơ cho đối tác thu mua', 'sort_order' => 4,
        ]));

        // ── Business Process: 1 Workflow, 5 Phase (§9.6 user journey) ─────
        $workflow = (new UpsertWorkflowAction())->handle(WorkflowData::from([
            'blueprint_version_id' => $version->id, 'capability_id' => $capVungTrong->id,
            'code' => 'WF-TXNG', 'name' => 'Quy trình truy xuất nguồn gốc nông sản',
        ]));

        $phKhaoSat = (new UpsertPhaseAction())->handle(PhaseData::from([
            'workflow_id' => $workflow->id, 'code' => 'PH-KS', 'name' => 'Khảo sát vùng trồng',
            'sort_order' => 0, 'is_initial' => true, 'auto_assign_data_collection' => true,
        ]));
        $phThuThap = (new UpsertPhaseAction())->handle(PhaseData::from([
            'workflow_id' => $workflow->id, 'code' => 'PH-TH', 'name' => 'Thu thập hồ sơ', 'sort_order' => 1,
        ]));
        $phChuanHoa = (new UpsertPhaseAction())->handle(PhaseData::from([
            'workflow_id' => $workflow->id, 'code' => 'PH-CH', 'name' => 'Chuẩn hóa dữ liệu', 'sort_order' => 2,
        ]));
        $phKiemTra = (new UpsertPhaseAction())->handle(PhaseData::from([
            'workflow_id' => $workflow->id, 'code' => 'PH-KT', 'name' => 'Kiểm tra & AI Validation', 'sort_order' => 3,
        ]));
        $phBanGiao = (new UpsertPhaseAction())->handle(PhaseData::from([
            'workflow_id' => $workflow->id, 'code' => 'PH-BG', 'name' => 'Bàn giao hồ sơ', 'sort_order' => 4,
        ]));

        // ── Checklists (definition — không phải Task Runtime, §5.4) ──────
        $clKhaoSat = (new UpsertChecklistAction())->handle(ChecklistData::from([
            'phase_id' => $phKhaoSat->id, 'code' => 'CL-KS-01', 'name' => 'Khảo sát thực địa vùng trồng',
            'action_description' => 'Ghi nhận hiện trạng canh tác, diện tích, cây trồng chính.',
        ]));
        (new UpsertChecklistAction())->handle(ChecklistData::from([
            'phase_id' => $phKhaoSat->id, 'code' => 'CL-KS-02', 'name' => 'Ghi nhận tọa độ GPS ranh giới lô',
        ]));
        (new UpsertChecklistAction())->handle(ChecklistData::from([
            'phase_id' => $phThuThap->id, 'code' => 'CL-TH-01',
            'name' => 'Thu thập hồ sơ pháp lý (giấy chứng nhận quyền sử dụng đất, hợp đồng canh tác)',
        ]));
        (new UpsertChecklistAction())->handle(ChecklistData::from([
            'phase_id' => $phThuThap->id, 'code' => 'CL-TH-02', 'name' => 'Thu thập ảnh/video hiện trạng canh tác',
            'required' => false,
        ]));
        $clChuanHoa = (new UpsertChecklistAction())->handle(ChecklistData::from([
            'phase_id' => $phChuanHoa->id, 'code' => 'CL-CH-01',
            'name' => 'Chuẩn hóa dữ liệu theo biểu mẫu chuẩn BM-01',
        ]));
        $clKiemTra = (new UpsertChecklistAction())->handle(ChecklistData::from([
            'phase_id' => $phKiemTra->id, 'code' => 'CL-KT-01', 'name' => 'AI kiểm tra tính đầy đủ hồ sơ',
        ]));
        (new UpsertChecklistAction())->handle(ChecklistData::from([
            'phase_id' => $phKiemTra->id, 'code' => 'CL-KT-02', 'name' => 'Tổ trưởng vùng phê duyệt hồ sơ',
            'need_approval' => true,
        ]));
        (new UpsertChecklistAction())->handle(ChecklistData::from([
            'phase_id' => $phBanGiao->id, 'code' => 'CL-BG-01', 'name' => 'Sinh mã QR truy xuất nguồn gốc',
        ]));
        (new UpsertChecklistAction())->handle(ChecklistData::from([
            'phase_id' => $phBanGiao->id, 'code' => 'CL-BG-02',
            'name' => 'Bàn giao hồ sơ điện tử cho đối tác thu mua', 'need_approval' => true,
        ]));

        // ── Resources — chỉ Reference, không copy (DP-08) ─────────────────
        (new UpsertResourceLinkAction())->handle(ResourceLinkData::from([
            'blueprint_version_id' => $version->id, 'checklist_id' => $clKhaoSat->id,
            'resource_type' => 'sop', 'resource_id' => $sopSurvey->id, 'is_required' => true,
        ]));
        (new UpsertResourceLinkAction())->handle(ResourceLinkData::from([
            'blueprint_version_id' => $version->id, 'checklist_id' => $clChuanHoa->id,
            'resource_type' => 'sop', 'resource_id' => $sopStandardize->id, 'is_required' => true,
        ]));
        (new UpsertResourceLinkAction())->handle(ResourceLinkData::from([
            'blueprint_version_id' => $version->id, 'checklist_id' => $clChuanHoa->id,
            'resource_type' => 'knowledge', 'resource_id' => $bm01Form->id, 'is_required' => true,
        ]));

        // ── AI Capabilities — Blueprint chỉ khai báo "AI cần hỗ trợ ở đâu", KHÔNG
        // gắn cứng agent/prompt (DP-06, §5.4: "Capability → Prompt → Agent → LLM" do
        // AI Configuration cấp Organization quyết định — xem ConfigureAiAction ở
        // HtxTienDuongDemoSeeder, nơi agent/prompt thật của HTX được gắn vào).
        (new UpsertAiCapabilityAction())->handle(AiCapabilityData::from([
            'blueprint_version_id' => $version->id, 'checklist_id' => $clKiemTra->id,
            'capability_code' => 'document_validation', 'name' => 'AI kiểm tra tính đầy đủ hồ sơ TXNG',
            'trigger_event' => 'checklist.started',
        ]));

        // ── Analytics — "cần đo cái gì", khác Dashboard (§5.4) ────────────
        (new UpsertAnalyticAction())->handle(AnalyticData::from([
            'blueprint_version_id' => $version->id, 'metric_code' => 'progress_pct',
            'name' => 'Tiến độ dự án', 'metric_type' => 'percentage', 'source_type' => 'checklist',
        ]));
        (new UpsertAnalyticAction())->handle(AnalyticData::from([
            'blueprint_version_id' => $version->id, 'metric_code' => 'checklist_completion_rate',
            'name' => 'Tỷ lệ checklist hoàn thành', 'metric_type' => 'percentage', 'source_type' => 'checklist',
        ]));
        (new UpsertAnalyticAction())->handle(AnalyticData::from([
            'blueprint_version_id' => $version->id, 'metric_code' => 'missing_docs_count',
            'name' => 'Số hồ sơ còn thiếu', 'metric_type' => 'count', 'source_type' => 'file',
        ]));
        (new UpsertAnalyticAction())->handle(AnalyticData::from([
            'blueprint_version_id' => $version->id, 'metric_code' => 'ai_validation_pass_rate',
            'name' => 'Tỷ lệ AI Validation passed', 'metric_type' => 'percentage', 'source_type' => 'ai_result',
        ]));

        // ── Deployment Settings — Role Mapping trừu tượng (A07 §12) ───────
        (new UpsertDeploymentRoleAction())->handle(DeploymentRoleData::from([
            'blueprint_version_id' => $version->id, 'role_code' => 'field_officer',
            'role_name' => 'Field Officer', 'description' => 'Nhân viên thị trường — thực hiện khảo sát & thu thập hồ sơ.',
            'sort_order' => 0,
        ]));
        (new UpsertDeploymentRoleAction())->handle(DeploymentRoleData::from([
            'blueprint_version_id' => $version->id, 'role_code' => 'supervisor',
            'role_name' => 'Supervisor', 'description' => 'Tổ trưởng vùng — phê duyệt hồ sơ, giám sát tiến độ.',
            'sort_order' => 1,
        ]));
        (new UpsertDeploymentRoleAction())->handle(DeploymentRoleData::from([
            'blueprint_version_id' => $version->id, 'role_code' => 'manager',
            'role_name' => 'Manager', 'description' => 'Giám đốc HTX — chịu trách nhiệm bàn giao đối tác.',
            'sort_order' => 2,
        ]));

        // ── Sidebar (trình bày — không bắt buộc cho Readiness) ────────────
        $sidebarGroup = (new UpsertSidebarItemAction())->handle(SidebarItemData::from([
            'blueprint_version_id' => $version->id, 'module_key' => 'ai-txng',
            'label' => 'AI Truy xuất nguồn gốc', 'icon' => 'leaf',
        ]));
        (new UpsertSidebarItemAction())->handle(SidebarItemData::from([
            'blueprint_version_id' => $version->id, 'parent_id' => $sidebarGroup->id,
            'module_key' => 'deployment.projects.index', 'label' => 'Dự án truy xuất', 'sort_order' => 0,
        ]));
        (new UpsertSidebarItemAction())->handle(SidebarItemData::from([
            'blueprint_version_id' => $version->id, 'parent_id' => $sidebarGroup->id,
            'module_key' => 'deployment.progress.index', 'label' => 'Tiến độ checklist', 'sort_order' => 1,
        ]));
        (new UpsertSidebarItemAction())->handle(SidebarItemData::from([
            'blueprint_version_id' => $version->id, 'parent_id' => $sidebarGroup->id,
            'module_key' => 'deployment.reports.pm', 'label' => 'Báo cáo bàn giao', 'sort_order' => 2,
        ]));

        // ── Submit for review → Publish (BR-019, DP-10) ───────────────────
        $version->update(['status' => BlueprintVersionStatus::ReadyForReview->value]);

        (new PublishBlueprintVersionAction(
            app(ValidateBlueprintIntegrityHandler::class),
            app(ValidateBlueprintReadinessHandler::class),
        ))->handle($version->fresh(), $systemUser->id);

        $this->command?->info('  ✓ Blueprint BP-TXNG v1.0.0 đã dựng đầy đủ & publish.');
    }

    /** @return array{0: SopProcess, 1: SopProcess} */
    private function seedSopResources(Organization $systemOrg, User $systemUser): array
    {
        $survey = SopProcess::withoutTenant()->firstOrCreate(
            ['organization_id' => $systemOrg->id, 'code' => 'SOP-KS-01'],
            [
                'title'          => 'Quy trình khảo sát thực địa vùng trồng',
                'description'    => 'Hướng dẫn khảo sát hiện trạng canh tác, đo đạc ranh giới và ghi nhận GPS lô sản xuất.',
                'type'           => SopType::Internal->value,
                'status'         => SopStatus::Approved->value,
                'version'        => 1,
                'owner_id'       => $systemUser->id,
                'approved_by'    => $systemUser->id,
                'approved_at'    => now(),
                'effective_date' => now()->toDateString(),
                'created_by'     => $systemUser->id,
            ]
        );

        $standardize = SopProcess::withoutTenant()->firstOrCreate(
            ['organization_id' => $systemOrg->id, 'code' => 'SOP-CH-01'],
            [
                'title'          => 'Quy trình chuẩn hóa & số hóa hồ sơ vùng trồng',
                'description'    => 'Hướng dẫn nhập liệu, chuẩn hóa hồ sơ khảo sát/thu thập theo biểu mẫu chuẩn trước khi AI kiểm tra.',
                'type'           => SopType::Internal->value,
                'status'         => SopStatus::Approved->value,
                'version'        => 1,
                'owner_id'       => $systemUser->id,
                'approved_by'    => $systemUser->id,
                'approved_at'    => now(),
                'effective_date' => now()->toDateString(),
                'created_by'     => $systemUser->id,
            ]
        );

        return [$survey, $standardize];
    }

    private function seedFormResource(Organization $systemOrg, User $systemUser): KcItem
    {
        $category = KcCategory::withoutTenant()->firstOrCreate(
            ['organization_id' => $systemOrg->id, 'slug' => 'bieu-mau-truy-xuat-nguon-goc'],
            [
                'uuid'       => (string) Str::uuid(),
                'name'       => 'Biểu mẫu & Tài liệu truy xuất nguồn gốc',
                'is_active'  => true,
                'created_by' => $systemUser->id,
            ]
        );

        return KcItem::withoutTenant()->firstOrCreate(
            ['organization_id' => $systemOrg->id, 'slug' => 'bm-01-bieu-mau-khao-sat-vung-trong'],
            [
                'uuid'        => (string) Str::uuid(),
                'category_id' => $category->id,
                'title'       => 'BM-01 — Biểu mẫu khảo sát vùng trồng',
                'summary'     => 'Biểu mẫu chuẩn thu thập thông tin vùng trồng khi khảo sát thực địa.',
                'type'        => 'form',
                'status'      => 'approved',
                'visibility'  => 'internal',
                'version'     => 1,
                'owner_id'    => $systemUser->id,
                'approved_by' => $systemUser->id,
                'approved_at' => now(),
                'created_by'  => $systemUser->id,
            ]
        );
    }
}
