<?php

namespace Modules\BusinessBlueprint\Features\BlueprintAuthoring\Queries;

use App\Shared\Contracts\QueryHandlerInterface;
use App\Shared\Contracts\QueryInterface;
use Modules\BusinessBlueprint\Models\BlueprintVersion;

/**
 * Load toàn bộ cây Blueprint (outcome→capability→workflow→phase→checklist→resource/ai)
 * của 1 version — dùng cho màn hình admin xây cây (giống RubricAuthoringController@tree).
 */
class GetBlueprintTreeHandler implements QueryHandlerInterface
{
    public function handle(QueryInterface $query): BlueprintVersion
    {
        /** @var GetBlueprintTreeQuery $query */
        return BlueprintVersion::with([
            'blueprint.businessSolution',
            'outcomes',
            'capabilities.workflows.phases.checklists.resourceLinks',
            'capabilities.workflows.phases.checklists.aiCapabilities',
            'analytics',
            'deploymentRoles',
            'sidebarItems' => fn ($q) => $q->whereNull('parent_id'),
            'sidebarItems.children',
        ])->findOrFail($query->blueprintVersionId);
    }
}
