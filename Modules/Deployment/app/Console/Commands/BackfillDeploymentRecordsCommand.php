<?php

namespace Modules\Deployment\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Modules\BusinessBlueprint\Enums\BlueprintVersionStatus;
use Modules\BusinessBlueprint\Models\Blueprint;
use Modules\Deployment\Enums\DeploymentStatus;
use Modules\Deployment\Models\Deployment;
use Modules\Deployment\Models\DeploymentSnapshot;
use Modules\Deployment\Models\DeploymentTarget;
use Modules\OrganizationSolution\Models\OrganizationSolution;

/**
 * Tuỳ chọn (spec §4.6) — tạo deployments/deployment_snapshots hồi tố cho các
 * DeploymentTarget cũ (deployment_id = NULL, đang chạy qua luồng VerticalRegistry
 * cũ) để dần đưa 100% Runtime về có deployment_id hợp lệ.
 *
 * Chỉ backfill được nếu đã tồn tại OrganizationSolution tương ứng (organization_id +
 * business_solution suy từ vertical_code → "BP-{VERTICAL_CODE}", xem
 * business-blueprint:migrate-vertical-templates /
 * organization-solution:migrate-org-vertical-templates) — nếu chưa, bỏ qua và cảnh báo.
 */
class BackfillDeploymentRecordsCommand extends Command
{
    protected $signature = 'deployment:backfill-records {--dry-run : Chỉ hiển thị sẽ làm gì, không ghi DB}';
    protected $description = 'Backfill deployments/deployment_snapshots cho DeploymentTarget cũ chưa có deployment_id';

    public function handle(): int
    {
        $dryRun = (bool) $this->option('dry-run');

        $targets = DeploymentTarget::withoutTenant()->whereNull('deployment_id')->get();

        if ($targets->isEmpty()) {
            $this->info('Không có DeploymentTarget nào cần backfill (tất cả đã có deployment_id).');
            return self::SUCCESS;
        }

        foreach ($targets as $target) {
            $blueprint = Blueprint::where('code', 'BP-' . strtoupper($target->vertical_code))->first();
            if (! $blueprint) {
                $this->warn("Target#{$target->id} (vertical={$target->vertical_code}) — chưa có Blueprint tương ứng, bỏ qua.");
                continue;
            }

            $orgSolution = OrganizationSolution::withoutTenant()
                ->where('organization_id', $target->organization_id)
                ->where('business_solution_id', $blueprint->business_solution_id)
                ->first();

            if (! $orgSolution) {
                $this->warn("Target#{$target->id} (org {$target->organization_id}) — chưa có OrganizationSolution tương ứng, bỏ qua.");
                continue;
            }

            $version = $blueprint->currentVersion;
            if (! $version || $version->status !== BlueprintVersionStatus::Published->value) {
                $this->warn("Target#{$target->id} — Blueprint \"{$blueprint->code}\" chưa có version published, bỏ qua.");
                continue;
            }

            $this->info("Backfill target#{$target->id} (org {$target->organization_id}, vertical={$target->vertical_code})" . ($dryRun ? ' (dry-run)' : ''));

            if ($dryRun) {
                continue;
            }

            DB::transaction(function () use ($target, $blueprint, $version, $orgSolution) {
                $deployment = Deployment::create([
                    'organization_id'          => $target->organization_id,
                    'organization_solution_id' => $orgSolution->id,
                    'business_solution_id'     => $blueprint->business_solution_id,
                    'blueprint_id'              => $blueprint->id,
                    'blueprint_version_id'      => $version->id,
                    'project_id'                => $target->project_id,
                    'deployed_by'               => $target->created_by,
                    'status'                    => DeploymentStatus::Completed->value,
                    'started_at'                => $target->created_at,
                    'completed_at'              => $target->created_at,
                ]);

                DeploymentSnapshot::create([
                    'organization_id'      => $target->organization_id,
                    'deployment_id'        => $deployment->id,
                    'snapshot_type'        => 'blueprint',
                    'blueprint_version_id' => $version->id,
                    'created_at'           => $target->created_at,
                ]);

                $target->update([
                    'deployment_id'        => $deployment->id,
                    'blueprint_version_id' => $version->id,
                ]);
            });
        }

        return self::SUCCESS;
    }
}
