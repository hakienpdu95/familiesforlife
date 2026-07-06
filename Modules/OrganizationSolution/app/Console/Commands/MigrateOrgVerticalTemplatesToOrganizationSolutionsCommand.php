<?php

namespace Modules\OrganizationSolution\Console\Commands;

use App\Foundation\Vertical\VerticalTemplate;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Modules\BusinessBlueprint\Enums\BlueprintVersionStatus;
use Modules\BusinessBlueprint\Models\Blueprint;
use Modules\OrganizationSolution\Enums\OrganizationSolutionStatus;
use Modules\OrganizationSolution\Models\OrganizationSolution;

/**
 * Migration dữ liệu 1 lần (spec §3.9): đọc `vertical_templates` có `organization_id`
 * SET (bản đã "activate" theo tổ chức hiện tại, `source_template_id` trỏ về bản thư
 * viện gốc), tạo `organization_solutions` tương ứng trỏ về `blueprint_version_id` đã
 * tạo ở Bước 2 (`business-blueprint:migrate-vertical-templates`), và suy ra
 * `organization_checklist_configs` từ phần khác biệt (diff) giữa bản clone của tổ
 * chức và bản thư viện gốc — checklist item bị tổ chức xoá thủ công trước đây được
 * ghi nhận thành `enabled=false` thay vì mất dữ liệu.
 *
 * Giới hạn đã biết: VerticalTemplate không có khái niệm Capability (chỉ có danh
 * sách phase phẳng, giống Blueprint được sinh tự động ở Bước 2 với đúng 1 Capability
 * "default" duy nhất) — nên không có gì để diff ở tầng Capability, bỏ qua
 * organization_capability_configs. Checklist item tổ chức THÊM MỚI (không có trong
 * bản thư viện) không thể biểu diễn qua organization_checklist_configs (chỉ tham
 * chiếu blueprint_checklist_id có sẵn) — lệnh sẽ CẢNH BÁO thay vì âm thầm bỏ qua.
 */
class MigrateOrgVerticalTemplatesToOrganizationSolutionsCommand extends Command
{
    protected $signature = 'organization-solution:migrate-org-vertical-templates {--dry-run : Chỉ hiển thị sẽ làm gì, không ghi DB}';
    protected $description = 'Migrate vertical_templates đã activate theo tổ chức (organization_id SET) sang OrganizationSolution';

    public function handle(): int
    {
        $dryRun = (bool) $this->option('dry-run');

        $templates = VerticalTemplate::whereNotNull('organization_id')
            ->whereNotNull('source_template_id')
            ->with(['sourceTemplate.phases.checklistItems', 'phases.checklistItems'])
            ->get();

        if ($templates->isEmpty()) {
            $this->info('Không có vertical_templates theo tổ chức (organization_id SET) nào để migrate.');
            return self::SUCCESS;
        }

        foreach ($templates as $template) {
            $sourceTemplate = $template->sourceTemplate;
            if (! $sourceTemplate) {
                $this->warn("Bỏ qua template#{$template->id} (org {$template->organization_id}) — không tìm thấy source_template_id.");
                continue;
            }

            $blueprint = Blueprint::where('code', 'BP-' . strtoupper($sourceTemplate->code))->first();
            if (! $blueprint) {
                $this->warn("Bỏ qua template#{$template->id} — chưa migrate Blueprint cho \"{$sourceTemplate->code}\" (chạy business-blueprint:migrate-vertical-templates trước).");
                continue;
            }

            $version = $blueprint->currentVersion;
            if (! $version || $version->status !== BlueprintVersionStatus::Published->value) {
                $this->warn("Bỏ qua template#{$template->id} — Blueprint \"{$blueprint->code}\" chưa có version published.");
                continue;
            }

            if (OrganizationSolution::withoutTenant()
                ->where('organization_id', $template->organization_id)
                ->where('business_solution_id', $blueprint->business_solution_id)
                ->exists()) {
                $this->line("Bỏ qua org {$template->organization_id} / {$blueprint->code} — đã migrate trước đó.");
                continue;
            }

            $this->info("Migrate template#{$template->id} (org {$template->organization_id}, \"{$template->code}\")" . ($dryRun ? ' (dry-run)' : ''));

            if ($dryRun) {
                continue;
            }

            DB::transaction(fn () => $this->migrateOne($template, $blueprint, $version));
        }

        return self::SUCCESS;
    }

    private function migrateOne(VerticalTemplate $template, Blueprint $blueprint, $version): void
    {
        $orgSolution = OrganizationSolution::create([
            'organization_id'      => $template->organization_id,
            'business_solution_id' => $blueprint->business_solution_id,
            'blueprint_version_id' => $version->id,
            'name'                  => $template->label,
            'owner_id'              => $template->activated_by ?? 1,
            'status'                => OrganizationSolutionStatus::Running->value,
            'activated_at'          => $template->activated_at ?? now(),
        ]);

        $this->diffChecklists($template, $version, $orgSolution);
    }

    private function diffChecklists(VerticalTemplate $template, $version, OrganizationSolution $orgSolution): void
    {
        $version->loadMissing('capabilities.workflows.phases.checklists');

        $libraryChecklistCodes = $template->sourceTemplate->phases
            ->flatMap(fn ($phase) => $phase->checklistItems->pluck('key'))
            ->all();

        $orgChecklistCodes = $template->phases
            ->flatMap(fn ($phase) => $phase->checklistItems->pluck('key'))
            ->all();

        $blueprintChecklists = $version->capabilities->flatMap->workflows->flatMap->phases->flatMap->checklists->keyBy('code');

        // Checklist bị tổ chức XOÁ thủ công (có ở thư viện, không có ở bản của tổ chức)
        // → enabled=false, KHÔNG mất dữ liệu (Blueprint vẫn giữ nguyên định nghĩa gốc).
        foreach (array_diff($libraryChecklistCodes, $orgChecklistCodes) as $removedCode) {
            $blueprintChecklist = $blueprintChecklists->get($removedCode);
            if ($blueprintChecklist) {
                $orgSolution->checklistConfigs()->create([
                    'blueprint_checklist_id' => $blueprintChecklist->id,
                    'enabled'                 => false,
                ]);
            }
        }

        // Checklist tổ chức TỰ THÊM (không có trong thư viện gốc) — không có
        // blueprint_checklist_id để tham chiếu, không thể migrate tự động.
        foreach (array_diff($orgChecklistCodes, $libraryChecklistCodes) as $addedCode) {
            $this->warn("  → Checklist \"{$addedCode}\" tổ chức tự thêm (không có trong Blueprint gốc) — cần rà soát thủ công.");
        }
    }
}
