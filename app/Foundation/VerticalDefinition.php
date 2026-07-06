<?php

namespace App\Foundation;

interface VerticalDefinition
{
    public function code(): string;
    public function label(): string;
    public function targetLabel(): string;
    public function targetOrgCategory(): string;

    // DEFAULT labels — không đọc trực tiếp ở runtime.
    // Luôn dùng VerticalConfigService::hierarchyLabels($orgId, ...).
    public function defaultSiteLabel(): ?string;
    public function areaLabel(): ?string;
    public function lotLabel(): ?string;
    public function itemLabel(): ?string;
    public function defaultItemCodePrefix(): ?string;

    public function phases(): array;
    /** Map key => nhãn hiển thị, cùng thứ tự với phases() — dùng cho UI, không dùng để so sánh/index. */
    public function phaseLabels(): array;
    public function defaultChecklist(): array;
    public function readinessTemplateSlag(): ?string;
    public function exportConfig(): ?array;

    public function dataCollectionTemplateSlug(): ?string;
    public function initialPhaseKey(): string;
    public function autoAssignsDataCollection(string $phaseKey): bool;

    // SEED-ONLY — runtime dùng VerticalConfigService::activityTypes() / legalDocTypes()
    public function defaultActivityTypes(): ?array;
    public function defaultLegalDocTypes(): ?array;

    public function verticalRoles(): array;
    public function sidebarGroups(): array;

    /** Vertical này có đang active cho tổ chức hiện tại không (RequireVertical middleware). */
    public function isActive(): bool;
}
