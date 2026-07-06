<?php

namespace Modules\Deployment\Actions;

use Closure;
use DomainException;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Lorisleiva\Actions\Concerns\AsAction;
use Modules\BusinessBlueprint\Enums\BlueprintVersionStatus;
use Modules\BusinessBlueprint\Foundation\BlueprintToVerticalDefinitionAdapter;
use Modules\BusinessBlueprint\Models\BlueprintVersion;
use Modules\Deployment\Data\CreateVerticalProjectData;
use Modules\Deployment\Enums\DeploymentStatus;
use Modules\Deployment\Models\Deployment;
use Modules\Deployment\Models\DeploymentChecklistItem;
use Modules\Deployment\Models\DeploymentConfigSnapshotItem;
use Modules\Deployment\Models\DeploymentLog;
use Modules\Deployment\Models\DeploymentSnapshot;
use Modules\Deployment\Models\DeploymentTarget;
use Modules\OrganizationSolution\Enums\OrganizationSolutionStatus;
use Modules\OrganizationSolution\Models\OrganizationSolution;
use Modules\Project\Enums\ProjectStatus;
use Modules\Project\Models\Project;
use Throwable;

/**
 * Deployment Engine (spec §4.5) — 6 bước xử lý, đứng TRƯỚC luồng hiện tại
 * (CreateVerticalProjectAction, đã có — KHÔNG sửa) để ghi nhận hành động deploy.
 *
 * Bước 3 KHÔNG gọi lại `CreateDeploymentTargetAction` hiện có: action đó phục vụ
 * luồng agency triển khai HỘ một tổ chức khác (tra/tạo Organization theo tax_code).
 * Ở đây tổ chức tự kích hoạt Solution cho CHÍNH MÌNH (self-service, Module
 * OrganizationSolution) — target_organization_id = organization_id, không tra/tạo
 * Organization mới. Logic tạo DeploymentTarget + seed checklist được viết lại ở đây
 * cho đúng ngữ nghĩa, KHÔNG động vào action cũ.
 */
class DeployOrganizationSolutionAction
{
    use AsAction;

    /**
     * CỐ Ý không bọc toàn bộ 6 bước trong 1 DB::transaction() duy nhất: mỗi bước tự
     * log kết quả (thành công/lỗi) ngay khi xảy ra — nếu dùng 1 transaction bao trùm,
     * một exception ở bước sau sẽ ROLLBACK luôn cả bản ghi `deployments` và toàn bộ
     * log lỗi đã ghi trước đó, xoá sạch audit trail của chính lần deploy thất bại đó
     * (phát hiện qua test). Mỗi bước ghi log ngay lập tức (tự commit), giữ nguyên
     * những gì đã hoàn thành (VD Project đã tạo ở bước 3) để người vận hành xử lý tiếp
     * khi 1 bước sau đó thất bại — đúng tinh thần "ghi nhận hành động deploy" từng bước.
     */
    public function handle(OrganizationSolution $orgSolution): Deployment
    {
        $version = $orgSolution->blueprintVersion()->firstOrFail();

        $deployment = Deployment::create([
            'organization_id'          => $orgSolution->organization_id,
            'organization_solution_id' => $orgSolution->id,
            'business_solution_id'     => $orgSolution->business_solution_id,
            'blueprint_id'             => $version->blueprint_id,
            'blueprint_version_id'     => $version->id,
            'deployed_by'              => auth()->id(),
            'status'                   => DeploymentStatus::Pending->value,
            'started_at'               => now(),
        ]);

        $project = null;

        try {
            $this->log($deployment, 'validate_blueprint', fn () => $this->validateBlueprint($version));

            $orgSolution->loadMissing([
                'capabilityConfigs', 'workflowConfigs', 'checklistConfigs',
                'roleMappings', 'aiConfigs', 'resourceOverrides', 'dashboardWidgets',
            ]);
            $this->log($deployment, 'read_config', fn () => sprintf(
                '%d capability, %d workflow, %d checklist config đã đọc.',
                $orgSolution->capabilityConfigs->count(),
                $orgSolution->workflowConfigs->count(),
                $orgSolution->checklistConfigs->count(),
            ));

            $adapter = new BlueprintToVerticalDefinitionAdapter($version);

            $this->log($deployment, 'generate_runtime', function () use (&$project, $deployment, $orgSolution, $version, $adapter) {
                $project = $this->generateRuntime($deployment, $orgSolution, $version, $adapter);

                return "Project #{$project->id} (\"{$project->name}\") đã tạo.";
            });

            $this->log($deployment, 'init_dashboard', fn () => sprintf(
                '%d dashboard widget sẵn sàng.',
                $orgSolution->dashboardWidgets->where('enabled', true)->count()
            ));

            $this->log($deployment, 'init_ai_context', fn () => sprintf(
                '%d AI capability sẵn sàng.',
                $orgSolution->aiConfigs->where('enabled', true)->count()
            ));
        } catch (Throwable $e) {
            $deployment->update(['status' => DeploymentStatus::Failed->value]);
            throw $e;
        }

        $this->recordSnapshots($deployment, $version, $orgSolution);

        $deployment->update([
            'status'       => DeploymentStatus::Completed->value,
            'project_id'   => $project->id,
            'completed_at' => now(),
        ]);
        $orgSolution->update(['status' => OrganizationSolutionStatus::Running->value]);

        $this->log($deployment, 'complete', fn () => 'Deploy hoàn tất — OrganizationSolution chuyển sang running.');

        return $deployment->fresh();
    }

    private function validateBlueprint(BlueprintVersion $version): string
    {
        if ($version->status !== BlueprintVersionStatus::Published->value) {
            throw new DomainException('Blueprint Version không ở trạng thái published — không thể deploy.');
        }

        return 'Blueprint Version hợp lệ (published).';
    }

    /** Project + DeploymentTarget + checklist items phải atomic với nhau (transaction cục bộ, khác transaction bao ngoài — xem ghi chú ở handle()). */
    private function generateRuntime(
        Deployment $deployment,
        OrganizationSolution $orgSolution,
        BlueprintVersion $version,
        BlueprintToVerticalDefinitionAdapter $adapter,
    ): Project {
        return DB::transaction(function () use ($deployment, $orgSolution, $version, $adapter) {
            $project = CreateVerticalProjectAction::run(
                CreateVerticalProjectData::from([
                    'name'        => $orgSolution->name,
                    'code'        => strtoupper(Str::slug($orgSolution->name, '-')) . '-' . $orgSolution->id,
                    'status'      => ProjectStatus::Active,
                    'description' => null,
                    'start_date'  => null,
                    'end_date'    => null,
                ]),
                $adapter,
            );

            $target = DeploymentTarget::create([
                'organization_id'        => $orgSolution->organization_id,
                'project_id'             => $project->id,
                'vertical_code'          => $adapter->code(),
                'deployment_id'          => $deployment->id,
                'blueprint_version_id'   => $version->id,
                'target_organization_id' => $orgSolution->organization_id,
                'current_phase'          => $adapter->initialPhaseKey(),
                'created_by'             => auth()->id(),
            ]);

            foreach ($adapter->defaultChecklist() as $phase => $items) {
                foreach ($items as $item) {
                    DeploymentChecklistItem::create([
                        'organization_id'      => $orgSolution->organization_id,
                        'deployment_target_id' => $target->id,
                        'phase'                => $phase,
                        'item_key'             => $item['key'],
                        'item_label'           => $item['label'],
                        'is_required'          => $item['required'] ?? true,
                        'is_done'              => false,
                    ]);
                }
            }

            return $project;
        });
    }

    private function recordSnapshots(Deployment $deployment, BlueprintVersion $version, OrganizationSolution $orgSolution): void
    {
        DB::transaction(function () use ($deployment, $version, $orgSolution) {
            // 'blueprint': blueprint_version published là bất biến — bản thân
            // blueprint_version_id đã LÀ snapshot, không cần copy dữ liệu (xem migration).
            DeploymentSnapshot::create([
                'organization_id'      => $orgSolution->organization_id,
                'deployment_id'        => $deployment->id,
                'snapshot_type'        => 'blueprint',
                'blueprint_version_id' => $version->id,
            ]);

            // 'organization_config': manifest quan hệ (type+id) thay cho JSON blob.
            $configSnapshot = DeploymentSnapshot::create([
                'organization_id' => $orgSolution->organization_id,
                'deployment_id'   => $deployment->id,
                'snapshot_type'   => 'organization_config',
            ]);

            $manifest = [
                'capability_config' => $orgSolution->capabilityConfigs,
                'workflow_config'   => $orgSolution->workflowConfigs,
                'checklist_config'  => $orgSolution->checklistConfigs,
                'role_mapping'      => $orgSolution->roleMappings,
                'ai_config'         => $orgSolution->aiConfigs,
                'resource_override' => $orgSolution->resourceOverrides,
                'dashboard_widget'  => $orgSolution->dashboardWidgets,
            ];

            foreach ($manifest as $type => $items) {
                foreach ($items as $item) {
                    DeploymentConfigSnapshotItem::create([
                        'organization_id'        => $orgSolution->organization_id,
                        'deployment_snapshot_id' => $configSnapshot->id,
                        'configurable_type'      => $type,
                        'configurable_id'        => $item->id,
                    ]);
                }
            }
        });
    }

    private function log(Deployment $deployment, string $step, Closure $fn): void
    {
        try {
            $result = $fn();

            DeploymentLog::create([
                'organization_id' => $deployment->organization_id,
                'deployment_id'   => $deployment->id,
                'step'            => $step,
                'level'           => 'info',
                'message'         => is_string($result) ? $result : "{$step} OK",
            ]);
        } catch (Throwable $e) {
            DeploymentLog::create([
                'organization_id' => $deployment->organization_id,
                'deployment_id'   => $deployment->id,
                'step'            => $step,
                'level'           => 'error',
                'message'         => $e->getMessage(),
            ]);

            throw $e;
        }
    }
}
