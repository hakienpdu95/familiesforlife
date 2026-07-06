<?php

namespace Modules\BusinessBlueprint\Foundation;

use App\Foundation\VerticalDefinition;
use Illuminate\Support\Collection;
use Modules\BusinessBlueprint\Models\BlueprintVersion;

/**
 * Bọc 1 BlueprintVersion để nó "giả dạng" một VerticalDefinition như code cũ đang
 * mong đợi (spec §2.11) — nhờ đó Modules\Deployment và các code đang gọi
 * VerticalRegistry::resolve() không cần sửa ngay khi chuyển dần sang Blueprint mới.
 *
 * Các field hierarchy (site/area/lot/item label) và activity/legal-doc types là đặc
 * thù nghiệp vụ TXNG (traceability) — KHÔNG có trong schema Blueprint tổng quát
 * (spec Part 2.3, dùng chung mọi Business Solution). Trả về null giống hệt cách
 * App\Foundation\Vertical\DatabaseVertical hiện tại cũng trả null cho các field này
 * (default_hierarchy đã bị xoá khỏi vertical_templates) — tầng gọi
 * (VerticalConfigService) đã có fallback cứng cuối cùng, không vỡ luồng hiện tại.
 */
class BlueprintToVerticalDefinitionAdapter implements VerticalDefinition
{
    public function __construct(private readonly BlueprintVersion $version)
    {
        $this->version->loadMissing([
            'blueprint.businessSolution',
            'capabilities.workflows.phases.checklists',
            'deploymentRoles',
            'sidebarItems.children',
        ]);
    }

    public function code(): string
    {
        return $this->version->blueprint->code;
    }

    public function label(): string
    {
        return $this->version->blueprint->name;
    }

    public function targetLabel(): string
    {
        return $this->version->blueprint->businessSolution->name;
    }

    public function targetOrgCategory(): string
    {
        return $this->version->blueprint->businessSolution->target_customers[0] ?? 'general';
    }

    public function defaultSiteLabel(): ?string { return null; }
    public function areaLabel(): ?string { return null; }
    public function lotLabel(): ?string { return null; }
    public function itemLabel(): ?string { return null; }
    public function defaultItemCodePrefix(): ?string { return null; }
    public function defaultActivityTypes(): ?array { return null; }
    public function defaultLegalDocTypes(): ?array { return null; }
    public function readinessTemplateSlag(): ?string { return null; }
    public function exportConfig(): ?array { return null; }
    public function dataCollectionTemplateSlug(): ?string { return null; }

    public function phases(): array
    {
        return $this->allPhases()->pluck('code')->all();
    }

    public function phaseLabels(): array
    {
        return $this->allPhases()->pluck('name', 'code')->all();
    }

    public function defaultChecklist(): array
    {
        return $this->allPhases()
            ->mapWithKeys(fn ($phase) => [
                $phase->code => $phase->checklists->map(fn ($item) => [
                    'key'      => $item->code,
                    'label'    => $item->name,
                    'required' => $item->required,
                ])->all(),
            ])
            ->all();
    }

    public function initialPhaseKey(): string
    {
        $phases = $this->allPhases();

        return $phases->firstWhere('is_initial', true)?->code
            ?? $phases->first()?->code
            ?? 'draft';
    }

    public function autoAssignsDataCollection(string $phaseKey): bool
    {
        return (bool) $this->allPhases()->firstWhere('code', $phaseKey)?->auto_assign_data_collection;
    }

    /** Danh sách role code phẳng — khớp shape `default_roles` cũ (['pm','surveyor',...]). */
    public function verticalRoles(): array
    {
        return $this->version->deploymentRoles->pluck('role_code')->all();
    }

    /** group label => [['label'=>,'route'=>], ...] — khớp shape `sidebar_config` cũ. */
    public function sidebarGroups(): array
    {
        return $this->version->sidebarItems
            ->whereNull('parent_id')
            ->mapWithKeys(fn ($group) => [
                $group->label => $group->children->map(fn ($child) => [
                    'label' => $child->label,
                    'route' => $child->module_key,
                ])->all(),
            ])
            ->all();
    }

    private function allPhases(): Collection
    {
        return $this->version->capabilities->flatMap->workflows->flatMap->phases;
    }

    // VerticalRegistry::resolveFromBlueprint() chỉ trả về adapter này khi version đã
    // Published và (với $organizationId cụ thể) tổ chức đã thực sự deploy nó — nên luôn active.
    public function isActive(): bool { return true; }
}
