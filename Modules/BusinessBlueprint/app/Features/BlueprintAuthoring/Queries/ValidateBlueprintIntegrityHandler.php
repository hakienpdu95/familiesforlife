<?php

namespace Modules\BusinessBlueprint\Features\BlueprintAuthoring\Queries;

use App\Shared\Contracts\QueryHandlerInterface;
use App\Shared\Contracts\QueryInterface;
use Modules\BusinessBlueprint\Models\BlueprintVersion;

/**
 * Kiểm tra toàn vẹn cây Blueprint trước khi publish: không có node "mồ côi"
 * (tham chiếu chéo sang version khác) và không trùng code trong cùng phạm vi cha.
 */
class ValidateBlueprintIntegrityHandler implements QueryHandlerInterface
{
    /** @return array{valid: bool, errors: string[]} */
    public function handle(QueryInterface $query): array
    {
        /** @var ValidateBlueprintIntegrityQuery $query */
        $version = BlueprintVersion::with([
            'outcomes', 'capabilities', 'capabilities.workflows', 'capabilities.workflows.phases.checklists',
        ])->findOrFail($query->blueprintVersionId);

        $errors = [];

        $this->assertNoDuplicateCodes($version->outcomes, 'Outcome', $errors);
        $this->assertNoDuplicateCodes($version->capabilities, 'Capability', $errors);

        $outcomeIds = $version->outcomes->pluck('id');
        foreach ($version->capabilities as $capability) {
            if ($capability->outcome_id && ! $outcomeIds->contains($capability->outcome_id)) {
                $errors[] = "Capability {$capability->code}: outcome_id trỏ tới Outcome không thuộc version này (node mồ côi).";
            }
        }

        $allWorkflows = $version->capabilities->flatMap->workflows;
        $this->assertNoDuplicateCodes($allWorkflows, 'Workflow', $errors);

        $capabilityIds = $version->capabilities->pluck('id');
        foreach ($allWorkflows as $workflow) {
            if ($workflow->capability_id && ! $capabilityIds->contains($workflow->capability_id)) {
                $errors[] = "Workflow {$workflow->code}: capability_id trỏ tới Capability không thuộc version này (node mồ côi).";
            }

            $this->assertNoDuplicateCodes($workflow->phases, "Phase (workflow {$workflow->code})", $errors);

            foreach ($workflow->phases as $phase) {
                $this->assertNoDuplicateCodes($phase->checklists, "Checklist (phase {$phase->code})", $errors);
            }
        }

        return ['valid' => empty($errors), 'errors' => $errors];
    }

    private function assertNoDuplicateCodes(iterable $items, string $label, array &$errors): void
    {
        $codes = collect($items)->pluck('code');
        $duplicates = $codes->duplicates();

        foreach ($duplicates->unique() as $code) {
            $errors[] = "{$label}: code \"{$code}\" bị trùng trong cùng phạm vi.";
        }
    }
}
