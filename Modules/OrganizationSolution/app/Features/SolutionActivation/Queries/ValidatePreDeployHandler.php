<?php

namespace Modules\OrganizationSolution\Features\SolutionActivation\Queries;

use App\Shared\Contracts\QueryHandlerInterface;
use App\Shared\Contracts\QueryInterface;
use Modules\AiCopilot\Models\AiAgent;
use Modules\AiCopilot\Models\AiPrompt;
use Modules\BusinessBlueprint\Enums\BlueprintVersionStatus;
use Modules\OrganizationSolution\Models\OrganizationSolution;

/**
 * 7 điều kiện Validation trước Deploy (A07 §14, spec §3.7).
 *
 * @return array{ready: bool, criteria: array<int, array{key: string, label: string, passed: bool}>}
 */
class ValidatePreDeployHandler implements QueryHandlerInterface
{
    public function handle(QueryInterface $query): array
    {
        /** @var ValidatePreDeployQuery $query */
        $orgSolution = OrganizationSolution::with([
            'blueprintVersion.deploymentRoles',
            'blueprintVersion.resourceLinks',
            'capabilityConfigs', 'workflowConfigs', 'roleMappings', 'aiConfigs', 'dashboardWidgets',
        ])->findOrFail($query->organizationSolutionId);

        $version = $orgSolution->blueprintVersion;

        $mappedRoleCodes = $orgSolution->roleMappings->pluck('blueprint_role_code');
        $requiredRoleCodes = $version->deploymentRoles->pluck('role_code');
        $roleMappingComplete = $requiredRoleCodes->isNotEmpty()
            && $requiredRoleCodes->diff($mappedRoleCodes)->isEmpty();

        $aiConfigValid = $orgSolution->aiConfigs
            ->where('enabled', true)
            ->every(fn ($config) => $this->aiReferencesValid($config->ai_agent_id, $config->ai_prompt_id));

        $criteria = [
            ['key' => 'has_blueprint',   'label' => 'Có Blueprint (published)',      'passed' => (bool) $version && $version->status === BlueprintVersionStatus::Published->value],
            ['key' => 'has_capability',  'label' => 'Có Capability được bật',        'passed' => $orgSolution->capabilityConfigs->where('enabled', true)->isNotEmpty()],
            ['key' => 'has_workflow',    'label' => 'Có Workflow được bật',          'passed' => $orgSolution->workflowConfigs->where('enabled', true)->isNotEmpty()],
            ['key' => 'has_resource',    'label' => 'Có Resource tham chiếu',        'passed' => $version && $version->resourceLinks->isNotEmpty()],
            ['key' => 'role_mapping_complete', 'label' => 'Role Mapping hoàn chỉnh', 'passed' => $roleMappingComplete],
            ['key' => 'ai_config_valid', 'label' => 'AI Config hợp lệ',              'passed' => $aiConfigValid],
            ['key' => 'has_dashboard',   'label' => 'Dashboard hợp lệ',              'passed' => $orgSolution->dashboardWidgets->isNotEmpty()],
        ];

        return [
            'ready'    => collect($criteria)->every(fn ($c) => $c['passed']),
            'criteria' => $criteria,
        ];
    }

    private function aiReferencesValid(?int $agentId, ?int $promptId): bool
    {
        if (! $agentId && ! $promptId) {
            return false;
        }

        if ($agentId && ! AiAgent::whereKey($agentId)->exists()) {
            return false;
        }

        if ($promptId && ! AiPrompt::whereKey($promptId)->exists()) {
            return false;
        }

        return true;
    }
}
