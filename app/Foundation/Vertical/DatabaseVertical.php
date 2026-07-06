<?php

namespace App\Foundation\Vertical;

use App\Foundation\VerticalDefinition;

class DatabaseVertical implements VerticalDefinition
{
    public function __construct(private readonly VerticalTemplate $template) {}

    public function code(): string               { return $this->template->code; }
    public function label(): string              { return $this->template->label; }
    public function targetLabel(): string        { return $this->template->target_label; }
    public function targetOrgCategory(): string  { return $this->template->target_org_category; }

    // default_hierarchy/default_activity_types/default_legal_doc_types đã bị xoá khỏi
    // vertical_templates (docs/refactor-vertical-deployment.md §2.2) — dữ liệu mặc định giờ
    // sống trong vertical_config_items của chính bản mẫu thư viện, copy nguyên khi nhân bản
    // (Phase 5, chưa làm). Các method dưới giữ lại để implement đúng interface — luôn null,
    // tầng gọi (VerticalConfigService) đã có fallback cứng cuối cùng.
    public function defaultSiteLabel(): ?string      { return null; }
    public function areaLabel(): ?string             { return null; }
    public function lotLabel(): ?string              { return null; }
    public function itemLabel(): ?string             { return null; }
    public function defaultItemCodePrefix(): ?string { return null; }
    public function defaultActivityTypes(): ?array   { return null; }
    public function defaultLegalDocTypes(): ?array   { return null; }

    public function phases(): array
    {
        return $this->template->phases->pluck('key')->all();
    }

    public function phaseLabels(): array
    {
        return $this->template->phases->pluck('label', 'key')->all();
    }

    public function defaultChecklist(): array
    {
        return $this->template->phases
            ->mapWithKeys(fn (VerticalPhase $phase) => [
                $phase->key => $phase->checklistItems->map(fn (VerticalChecklistItem $item) => [
                    'key'      => $item->key,
                    'label'    => $item->label,
                    'required' => $item->is_required,
                ])->all(),
            ])
            ->all();
    }

    public function readinessTemplateSlag(): ?string { return $this->template->readiness_template_slug; }
    public function exportConfig(): ?array            { return $this->template->export_config; }
    public function verticalRoles(): array            { return $this->template->default_roles ?? []; }
    public function sidebarGroups(): array            { return $this->template->sidebar_config ?? []; }

    public function dataCollectionTemplateSlug(): ?string { return $this->template->data_collection_template_slug; }

    public function initialPhaseKey(): string
    {
        $phases = $this->template->phases;

        return $phases->firstWhere('is_initial', true)?->key
            ?? $phases->first()?->key
            ?? 'draft';
    }

    public function autoAssignsDataCollection(string $phaseKey): bool
    {
        return (bool) $this->template->phases->firstWhere('key', $phaseKey)?->auto_assign_data_collection;
    }

    public function template(): VerticalTemplate { return $this->template; }

    public function isActive(): bool { return $this->template->status === 'active'; }
}
