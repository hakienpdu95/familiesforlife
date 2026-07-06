<?php

namespace Modules\BusinessBlueprint\Features\BlueprintAuthoring\Queries;

use App\Shared\Contracts\QueryHandlerInterface;
use App\Shared\Contracts\QueryInterface;
use Modules\BusinessBlueprint\Enums\BlueprintVersionStatus;
use Modules\BusinessBlueprint\Models\BlueprintVersion;

/**
 * 12 tiêu chí Readiness Checklist (A04.1 §7.4) — bảng đối chiếu implement tại
 * docs/thuchoc/02-DAC-TA-THIET-KE-5-MODULE-MOI.md §2.8.
 *
 * Tiêu chí #9 "Có Deployment Settings": chỉ bắt buộc deploymentRoles() tồn tại
 * (RBAC là điều kiện triển khai thiết yếu — OrganizationSolution §3.7 coi Role
 * Mapping là "rất quan trọng"); sidebarItems() là trình bày/UI, không bắt buộc.
 *
 * @return array{ready: bool, criteria: array<int, array{key: string, label: string, passed: bool}>}
 */
class ValidateBlueprintReadinessHandler implements QueryHandlerInterface
{
    public function handle(QueryInterface $query): array
    {
        /** @var ValidateBlueprintReadinessQuery $query */
        $version = BlueprintVersion::with([
            'blueprint',
            'outcomes', 'capabilities', 'capabilities.workflows.phases.checklists',
            'resourceLinks', 'deploymentRoles',
        ])->findOrFail($query->blueprintVersionId);

        $blueprint = $version->blueprint;
        $workflows = $version->capabilities->flatMap->workflows;
        $phases    = $workflows->flatMap->phases;

        $criteria = [
            ['key' => 'has_business_solution', 'label' => 'Có Business Solution',      'passed' => (bool) $blueprint->business_solution_id],
            ['key' => 'overview_complete',     'label' => 'Overview đầy đủ',            'passed' => filled($blueprint->name) && filled($blueprint->description)],
            ['key' => 'has_outcome',           'label' => '≥1 Business Outcome',        'passed' => $version->outcomes->isNotEmpty()],
            ['key' => 'has_capability',        'label' => '≥1 Business Capability',     'passed' => $version->capabilities->isNotEmpty()],
            ['key' => 'has_workflow',          'label' => '≥1 Workflow',                'passed' => $workflows->isNotEmpty()],
            ['key' => 'workflow_has_phase',    'label' => 'Workflow có Phase',          'passed' => $workflows->isNotEmpty() && $workflows->every(fn ($w) => $w->phases->isNotEmpty())],
            ['key' => 'phase_has_checklist',   'label' => 'Phase có Checklist',         'passed' => $phases->isNotEmpty() && $phases->every(fn ($p) => $p->checklists->isNotEmpty())],
            ['key' => 'has_resource',          'label' => 'Có Resource tham chiếu',     'passed' => $version->resourceLinks->isNotEmpty()],
            ['key' => 'has_deployment_settings', 'label' => 'Có Deployment Settings',   'passed' => $version->deploymentRoles->isNotEmpty()],
            ['key' => 'has_valid_version',      'label' => 'Có Version hợp lệ (semver)', 'passed' => (bool) preg_match('/^\d+\.\d+\.\d+$/', $version->version)],
            ['key' => 'has_author',             'label' => 'Có Author',                 'passed' => (bool) $blueprint->created_by],
            ['key' => 'status_ready',           'label' => 'Status = sẵn sàng',          'passed' => in_array($version->status, [
                BlueprintVersionStatus::ReadyForReview->value,
                BlueprintVersionStatus::Approved->value,
            ], true)],
        ];

        return [
            'ready'    => collect($criteria)->every(fn ($c) => $c['passed']),
            'criteria' => $criteria,
        ];
    }
}
