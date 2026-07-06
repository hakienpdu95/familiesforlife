<?php

namespace Modules\BusinessBlueprint\Console\Commands;

use App\Foundation\Vertical\VerticalTemplate;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Modules\BusinessBlueprint\Enums\BlueprintVersionStatus;
use Modules\BusinessBlueprint\Models\Blueprint;
use Modules\BusinessBlueprint\Models\BlueprintCapability;
use Modules\BusinessBlueprint\Models\BlueprintChecklist;
use Modules\BusinessBlueprint\Models\BlueprintDeploymentRole;
use Modules\BusinessBlueprint\Models\BlueprintPhase;
use Modules\BusinessBlueprint\Models\BlueprintSidebarItem;
use Modules\BusinessBlueprint\Models\BlueprintVersion;
use Modules\BusinessBlueprint\Models\BlueprintWorkflow;
use Modules\BusinessSolution\Models\BusinessSolution;
use Modules\BusinessSolution\Models\Vertical;

/**
 * Migration dữ liệu 1 lần (spec §2.11): đọc toàn bộ `vertical_templates` có
 * `organization_id IS NULL` (bản thư viện), tạo tương ứng `business_solutions`
 * (nếu chưa có) + `blueprints` + `blueprint_versions` (version=1.0.0, status=published)
 * + copy `vertical_phases`→`blueprint_phases`, `vertical_checklist_items`→`blueprint_checklists`,
 * `vertical_templates.sidebar_config`/`default_roles`→`blueprint_sidebar_items`/`blueprint_deployment_roles`.
 *
 * Blueprint bắt buộc phase thuộc về 1 workflow (thuộc 1 capability) — VerticalTemplate
 * không có khái niệm capability/workflow (chỉ có danh sách phase phẳng), nên mỗi
 * template tạo ra đúng 1 capability + 1 workflow "mặc định" để chứa toàn bộ phase cũ.
 */
class MigrateVerticalTemplatesToBlueprintsCommand extends Command
{
    protected $signature = 'business-blueprint:migrate-vertical-templates {--dry-run : Chỉ hiển thị sẽ làm gì, không ghi DB}';
    protected $description = 'Migrate thư viện vertical_templates (organization_id NULL) sang Blueprint/BusinessSolution';

    public function handle(): int
    {
        $dryRun = (bool) $this->option('dry-run');

        $templates = VerticalTemplate::whereNull('organization_id')->with('phases.checklistItems')->get();

        if ($templates->isEmpty()) {
            $this->info('Không có vertical_templates thư viện (organization_id NULL) nào để migrate.');
            return self::SUCCESS;
        }

        foreach ($templates as $template) {
            if (Blueprint::where('code', 'BP-' . strtoupper($template->code))->exists()) {
                $this->line("Bỏ qua {$template->code} — đã migrate trước đó.");
                continue;
            }

            $this->info("Migrate template \"{$template->code}\"" . ($dryRun ? ' (dry-run)' : ''));

            if ($dryRun) {
                continue;
            }

            DB::transaction(fn () => $this->migrateTemplate($template));
        }

        return self::SUCCESS;
    }

    private function migrateTemplate(VerticalTemplate $template): void
    {
        $vertical = Vertical::firstOrCreate(
            ['code' => $template->code],
            ['name' => $template->label, 'status' => 'active']
        );

        $businessSolution = BusinessSolution::create([
            'vertical_id'       => $vertical->id,
            'code'              => 'BP-' . strtoupper($template->code),
            'name'              => $template->label,
            'slug'              => Str::slug($template->label) . '-' . Str::lower($template->code),
            'short_description' => $template->target_label,
            'status'            => 'published',
            'visibility'        => 'private',
        ]);

        $blueprint = Blueprint::create([
            'business_solution_id' => $businessSolution->id,
            'code'                  => 'BP-' . strtoupper($template->code),
            'name'                  => $template->label,
            'description'           => "Migrate tự động từ vertical_templates#{$template->id} (\"{$template->code}\").",
            'status'                => BlueprintVersionStatus::Published->value,
        ]);

        $version = BlueprintVersion::create([
            'blueprint_id' => $blueprint->id,
            'version'      => '1.0.0',
            'status'       => BlueprintVersionStatus::Published->value,
            'published_at' => now(),
        ]);

        $blueprint->update(['current_version_id' => $version->id]);

        $capability = BlueprintCapability::create([
            'blueprint_version_id' => $version->id,
            'code'                  => 'default',
            'name'                  => 'Default Capability',
            'description'           => 'Sinh tự động khi migrate — VerticalTemplate không có khái niệm Capability.',
        ]);

        $workflow = BlueprintWorkflow::create([
            'blueprint_version_id' => $version->id,
            'capability_id'         => $capability->id,
            'code'                  => 'default',
            'name'                  => $template->label,
            'description'           => 'Sinh tự động khi migrate — VerticalTemplate không có khái niệm Workflow.',
        ]);

        foreach ($template->phases as $phase) {
            $newPhase = BlueprintPhase::create([
                'workflow_id'                  => $workflow->id,
                'code'                          => $phase->key,
                'name'                          => $phase->label,
                'sort_order'                    => $phase->sort_order,
                'is_initial'                    => $phase->is_initial,
                'auto_assign_data_collection'   => $phase->auto_assign_data_collection,
            ]);

            foreach ($phase->checklistItems as $item) {
                BlueprintChecklist::create([
                    'phase_id'    => $newPhase->id,
                    'code'         => $item->key,
                    'name'         => $item->label,
                    'required'     => $item->is_required,
                    'sort_order'   => $item->sort_order,
                ]);
            }
        }

        foreach (($template->default_roles ?? []) as $index => $roleCode) {
            BlueprintDeploymentRole::create([
                'blueprint_version_id' => $version->id,
                'role_code'             => $roleCode,
                'role_name'             => Str::headline($roleCode),
                'sort_order'            => $index,
            ]);
        }

        $sortOrder = 0;
        foreach (($template->sidebar_config ?? []) as $groupLabel => $items) {
            $group = BlueprintSidebarItem::create([
                'blueprint_version_id' => $version->id,
                'module_key'            => Str::slug($groupLabel),
                'label'                 => $groupLabel,
                'sort_order'            => $sortOrder++,
            ]);

            foreach ($items as $childSort => $item) {
                BlueprintSidebarItem::create([
                    'blueprint_version_id' => $version->id,
                    'parent_id'             => $group->id,
                    'module_key'            => $item['route'] ?? Str::slug($item['label']),
                    'label'                 => $item['label'],
                    'sort_order'            => $childSort,
                ]);
            }
        }
    }
}
