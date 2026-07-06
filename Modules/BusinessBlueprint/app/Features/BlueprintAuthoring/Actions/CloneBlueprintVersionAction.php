<?php

namespace Modules\BusinessBlueprint\Features\BlueprintAuthoring\Actions;

use Illuminate\Support\Facades\DB;
use Lorisleiva\Actions\Concerns\AsAction;
use Modules\BusinessBlueprint\Enums\BlueprintVersionStatus;
use Modules\BusinessBlueprint\Models\BlueprintVersion;

/**
 * Tạo bản ghi blueprint_versions mới với parent_version_id = $source->id, tăng version
 * theo IncrementSemVerAction, deep-clone toàn bộ outcomes/capabilities/workflows/phases/
 * checklists/resource_links/ai_capabilities/analytics/deployment_roles/sidebar_items sang
 * bản mới — KHÔNG clone bất kỳ dữ liệu Runtime nào (spec §2.6, Chương 5 A04.3).
 */
class CloneBlueprintVersionAction
{
    use AsAction;

    /** @param 'major'|'minor'|'patch' $level */
    public function handle(BlueprintVersion $source, string $level = 'minor'): BlueprintVersion
    {
        return DB::transaction(function () use ($source, $level) {
            $source->loadMissing([
                'outcomes', 'capabilities', 'capabilities.workflows.phases.checklists.resourceLinks',
                'capabilities.workflows.phases.checklists.aiCapabilities',
                'resourceLinks', 'aiCapabilities', 'analytics', 'deploymentRoles', 'sidebarItems',
            ]);

            $newVersion = BlueprintVersion::create([
                'blueprint_id'       => $source->blueprint_id,
                'version'            => app(IncrementSemVerAction::class)->handle($source->version, $level),
                'status'             => BlueprintVersionStatus::Draft->value,
                'parent_version_id'  => $source->id,
            ]);

            $outcomeIdMap = $this->cloneOutcomes($source, $newVersion);
            $capabilityIdMap = $this->cloneCapabilities($source, $newVersion, $outcomeIdMap);
            $workflowIdMap = $this->cloneWorkflows($source, $newVersion, $capabilityIdMap);
            $phaseIdMap = $this->clonePhases($source, $workflowIdMap);
            $checklistIdMap = $this->cloneChecklists($source, $phaseIdMap);

            $this->cloneResourceLinks($source, $newVersion, $checklistIdMap);
            $this->cloneAiCapabilities($source, $newVersion, $checklistIdMap);
            $this->cloneAnalytics($source, $newVersion);
            $this->cloneDeploymentRoles($source, $newVersion);
            $this->cloneSidebarItems($source, $newVersion);

            return $newVersion->fresh();
        });
    }

    /** @return array<int, int> old outcome id => new outcome id */
    private function cloneOutcomes(BlueprintVersion $source, BlueprintVersion $target): array
    {
        $map = [];
        foreach ($source->outcomes as $outcome) {
            $clone = $outcome->replicate(['id']);
            $clone->blueprint_version_id = $target->id;
            $clone->save();
            $map[$outcome->id] = $clone->id;
        }

        return $map;
    }

    /** @return array<int, int> old capability id => new capability id */
    private function cloneCapabilities(BlueprintVersion $source, BlueprintVersion $target, array $outcomeIdMap): array
    {
        $map = [];
        foreach ($source->capabilities as $capability) {
            $clone = $capability->replicate(['id']);
            $clone->blueprint_version_id = $target->id;
            $clone->outcome_id = $capability->outcome_id ? ($outcomeIdMap[$capability->outcome_id] ?? null) : null;
            $clone->save();
            $map[$capability->id] = $clone->id;
        }

        return $map;
    }

    /** @return array<int, int> old workflow id => new workflow id */
    private function cloneWorkflows(BlueprintVersion $source, BlueprintVersion $target, array $capabilityIdMap): array
    {
        $map = [];
        foreach ($source->capabilities->flatMap->workflows as $workflow) {
            $clone = $workflow->replicate(['id']);
            $clone->blueprint_version_id = $target->id;
            $clone->capability_id = $workflow->capability_id ? ($capabilityIdMap[$workflow->capability_id] ?? null) : null;
            $clone->save();
            $map[$workflow->id] = $clone->id;
        }

        return $map;
    }

    /** @return array<int, int> old phase id => new phase id */
    private function clonePhases(BlueprintVersion $source, array $workflowIdMap): array
    {
        $map = [];
        foreach ($source->capabilities->flatMap->workflows->flatMap->phases as $phase) {
            $clone = $phase->replicate(['id']);
            $clone->workflow_id = $workflowIdMap[$phase->workflow_id];
            $clone->save();
            $map[$phase->id] = $clone->id;
        }

        return $map;
    }

    /** @return array<int, int> old checklist id => new checklist id */
    private function cloneChecklists(BlueprintVersion $source, array $phaseIdMap): array
    {
        $map = [];
        foreach ($source->capabilities->flatMap->workflows->flatMap->phases->flatMap->checklists as $checklist) {
            $clone = $checklist->replicate(['id']);
            $clone->phase_id = $phaseIdMap[$checklist->phase_id];
            $clone->save();
            $map[$checklist->id] = $clone->id;
        }

        return $map;
    }

    private function cloneResourceLinks(BlueprintVersion $source, BlueprintVersion $target, array $checklistIdMap): void
    {
        foreach ($source->resourceLinks as $resourceLink) {
            $clone = $resourceLink->replicate(['id']);
            $clone->blueprint_version_id = $target->id;
            $clone->checklist_id = $resourceLink->checklist_id ? ($checklistIdMap[$resourceLink->checklist_id] ?? null) : null;
            $clone->save();
        }
    }

    private function cloneAiCapabilities(BlueprintVersion $source, BlueprintVersion $target, array $checklistIdMap): void
    {
        foreach ($source->aiCapabilities as $aiCapability) {
            $clone = $aiCapability->replicate(['id']);
            $clone->blueprint_version_id = $target->id;
            $clone->checklist_id = $aiCapability->checklist_id ? ($checklistIdMap[$aiCapability->checklist_id] ?? null) : null;
            $clone->save();
        }
    }

    private function cloneAnalytics(BlueprintVersion $source, BlueprintVersion $target): void
    {
        foreach ($source->analytics as $analytic) {
            $clone = $analytic->replicate(['id']);
            $clone->blueprint_version_id = $target->id;
            $clone->save();
        }
    }

    private function cloneDeploymentRoles(BlueprintVersion $source, BlueprintVersion $target): void
    {
        foreach ($source->deploymentRoles as $role) {
            $clone = $role->replicate(['id']);
            $clone->blueprint_version_id = $target->id;
            $clone->save();
        }
    }

    /** Self-referencing tree — clone theo 2 lượt: gốc trước, con sau (remap parent_id qua map). */
    private function cloneSidebarItems(BlueprintVersion $source, BlueprintVersion $target): void
    {
        $all = $source->sidebarItems()->get();
        $idMap = [];

        foreach ($all->whereNull('parent_id') as $item) {
            $clone = $item->replicate(['id']);
            $clone->blueprint_version_id = $target->id;
            $clone->parent_id = null;
            $clone->save();
            $idMap[$item->id] = $clone->id;
        }

        foreach ($all->whereNotNull('parent_id') as $item) {
            $clone = $item->replicate(['id']);
            $clone->blueprint_version_id = $target->id;
            $clone->parent_id = $idMap[$item->parent_id] ?? null;
            $clone->save();
            $idMap[$item->id] = $clone->id;
        }
    }
}
