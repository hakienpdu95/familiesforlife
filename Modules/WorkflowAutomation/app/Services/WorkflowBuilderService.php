<?php

namespace Modules\WorkflowAutomation\Services;

use App\Shared\Tenancy\TenantContext;
use Illuminate\Http\Request;
use Modules\WorkflowAutomation\Models\Workflow;
use Modules\WorkflowAutomation\Models\WorkflowStepHeader;

class WorkflowBuilderService
{
    public function createFromRequest(Request $request): Workflow
    {
        $data     = $this->validate($request);
        $workflow = Workflow::create($this->workflowAttributes($data));
        $this->syncTriggerParams($workflow, $data['trigger_params'] ?? []);
        $this->syncConditions($workflow, $data['conditions'] ?? []);
        $this->syncSteps($workflow, $data['steps'] ?? []);
        return $workflow;
    }

    public function updateFromRequest(Request $request, Workflow $workflow): Workflow
    {
        $data = $this->validate($request);
        $workflow->update($this->workflowAttributes($data));
        $this->syncTriggerParams($workflow, $data['trigger_params'] ?? []);
        $this->syncConditions($workflow, $data['conditions'] ?? []);
        $this->syncSteps($workflow, $data['steps'] ?? []);
        return $workflow;
    }

    private function validate(Request $request): array
    {
        return $request->validate([
            'name'                              => 'required|string|max:191',
            'description'                       => 'nullable|string|max:500',
            'trigger_type'                      => 'required|string|max:64',
            'trigger_params'                    => 'nullable|array',
            'trigger_params.*.param_key'        => 'required|string|max:64',
            'trigger_params.*.param_value'      => 'nullable|string|max:255',
            'trigger_params.*.param_type'       => 'required|integer|in:1,2,3,4',
            'condition_match'                   => 'required|integer|in:1,2,3',
            'cooldown_type'                     => 'required|integer|in:0,1,2,3,4,5,6',
            'is_active'                         => 'boolean',
            'priority'                          => 'integer|min:1|max:10',
            'conditions'                        => 'nullable|array',
            'conditions.*.field'                => 'required|string|max:128',
            'conditions.*.operator'             => 'required|string|max:32',
            'conditions.*.value'                => 'nullable|string|max:500',
            'conditions.*.value_type'           => 'required|integer|in:1,2,3,4',
            'steps'                             => 'nullable|array',
            'steps.*.action_type'               => 'required|string|max:64',
            'steps.*.step_type'                 => 'nullable|integer|in:1,2,3',
            'steps.*.step_label'                => 'nullable|string|max:191',
            'steps.*.delay_minutes'             => 'integer|min:0',
            'steps.*.halt_on_fail'              => 'nullable|boolean',
            'steps.*.step_output_key'           => 'nullable|string|max:64|regex:/^[a-z0-9_]*$/',
            'steps.*.action_config'             => 'nullable|array',
            'steps.*.condition_config'          => 'nullable|array',
            'steps.*.condition_config.match'    => 'nullable|string|in:ALL,ANY',
            'steps.*.condition_config.conditions'   => 'nullable|array',
            'steps.*.headers'                   => 'nullable|array',
            'steps.*.headers.*.header_key'      => 'required|string|max:128',
            'steps.*.headers.*.header_value'    => 'required|string|max:500',
        ]);
    }

    private function workflowAttributes(array $data): array
    {
        return [
            'organization_id' => TenantContext::getOrganizationId(),
            'name'            => $data['name'],
            'description'     => $data['description'] ?? null,
            'trigger_type'    => $data['trigger_type'],
            'condition_match' => $data['condition_match'],
            'cooldown_type'   => $data['cooldown_type'],
            'is_active'       => $data['is_active'] ?? false,
            'priority'        => $data['priority'] ?? 5,
            'created_by'      => auth()->id(),
            'updated_by'      => auth()->id(),
        ];
    }

    private function syncTriggerParams(Workflow $workflow, array $params): void
    {
        $workflow->triggerParams()->delete();
        foreach ($params as $param) {
            $workflow->triggerParams()->create([
                'param_key'   => $param['param_key'],
                'param_value' => $param['param_value'] ?? null,
                'param_type'  => $param['param_type'] ?? 1,
            ]);
        }
    }

    private function syncConditions(Workflow $workflow, array $conditions): void
    {
        $workflow->conditions()->delete();
        foreach (array_values($conditions) as $i => $cond) {
            $workflow->conditions()->create(array_merge($cond, ['sort_order' => $i]));
        }
    }

    private function syncSteps(Workflow $workflow, array $steps): void
    {
        $oldStepIds = $workflow->steps()->pluck('id');
        WorkflowStepHeader::whereIn('step_id', $oldStepIds)->delete();
        $workflow->steps()->delete();

        foreach (array_values($steps) as $i => $stepData) {
            $headers         = $stepData['headers'] ?? [];
            $actionConfig    = $stepData['action_config'] ?? [];
            $conditionConfig = $stepData['condition_config'] ?? null;

            // Remove non-column keys
            unset($stepData['headers'], $stepData['action_config'], $stepData['condition_config']);

            $attrs = array_merge($stepData, [
                'sort_order'       => $i,
                'step_type'        => (int) ($stepData['step_type'] ?? 1),
                'label'            => $stepData['step_label'] ?? null,
                'halt_on_fail'     => filter_var($stepData['halt_on_fail'] ?? false, FILTER_VALIDATE_BOOLEAN),
                'step_output_key'  => $stepData['step_output_key'] ?? null,
                'action_config'    => !empty($actionConfig) ? json_encode($actionConfig, JSON_UNESCAPED_UNICODE) : null,
                'condition_config' => $conditionConfig ? json_encode($conditionConfig, JSON_UNESCAPED_UNICODE) : null,
            ]);

            // Remove duplicate/unknown keys that might not be in fillable
            unset($attrs['step_label']);

            // Map action_config back to legacy flat columns for v1 executor compatibility
            foreach ([
                'email_to', 'email_subject', 'email_template',
                'notif_title', 'notif_body', 'notif_target',
                'update_model', 'update_field', 'update_value',
                'webhook_url', 'webhook_method', 'webhook_secret',
                'lead_status', 'lead_source', 'lead_assigned_to',
                'user_tag', 'user_status',
            ] as $flatKey) {
                if (isset($actionConfig[$flatKey])) {
                    $attrs[$flatKey] = $actionConfig[$flatKey];
                }
            }

            $step = $workflow->steps()->create($attrs);

            foreach ($headers as $h) {
                $step->headers()->create([
                    'header_key'   => $h['header_key'],
                    'header_value' => $h['header_value'],
                ]);
            }
        }
    }
}
