<?php

namespace Modules\Lead\Workflow;

use Modules\Lead\Actions\CreateLeadAction;
use Modules\Lead\Data\Requests\StoreLeadData;
use Modules\LeadPipelineStage\Queries\ListStagesHandler;
use Modules\LeadPipelineStage\Queries\ListStagesQuery;
use Modules\LeadSource\Queries\ListSourcesHandler;
use Modules\LeadSource\Queries\ListSourcesQuery;
use Modules\WorkflowAutomation\Contracts\ActionExecutor;
use Modules\WorkflowAutomation\Data\ActionResult;
use Modules\WorkflowAutomation\Data\TriggerPayload;
use Modules\WorkflowAutomation\Models\WorkflowStep;

class CreateLeadExecutor implements ActionExecutor
{
    public function __construct(
        private readonly ListStagesHandler  $stagesHandler,
        private readonly ListSourcesHandler $sourcesHandler,
    ) {}

    public function type(): string   { return 'lead.create'; }
    public function label(): string  { return 'Tạo Lead CRM'; }
    public function module(): string { return 'Lead'; }

    public function stepConfigFields(): array
    {
        return [
            ['key' => 'assigned_to', 'label' => 'Người phụ trách (user ID)', 'type' => 'number',
             'hint' => 'Để trống = không phân công'],
            ['key' => 'default_stage_code', 'label' => 'Stage mặc định', 'type' => 'text',
             'hint' => 'VD: new. Để trống = stage đầu tiên'],
            ['key' => 'source_code', 'label' => 'Nguồn', 'type' => 'text',
             'hint' => 'VD: workflow, survey. Để trống = workflow'],
        ];
    }

    public function execute(WorkflowStep $step, TriggerPayload $payload): ActionResult
    {
        $start = microtime(true);

        try {
            $orgId = $payload->organizationId
                ?? $step->workflow?->organization_id
                ?? config('lead.default_organization_id', 1);

            // Resolve stage
            $stages      = $this->stagesHandler->handle(new ListStagesQuery($orgId, activeOnly: true));
            $stageCode   = $step->default_stage_code ?? null;
            $targetStage = $stageCode
                ? $stages->firstWhere('code', $stageCode)
                : $stages->first();

            if (! $targetStage) {
                return ActionResult::fail('Không tìm thấy pipeline stage hợp lệ.', $this->ms($start));
            }

            // Resolve source
            $sources    = $this->sourcesHandler->handle(new ListSourcesQuery($orgId, activeOnly: true));
            $srcCode    = $step->source_code ?? 'workflow';
            $source     = $sources->firstWhere('code', $srcCode) ?? $sources->firstWhere('code', 'workflow');

            // Idempotent key — prevents duplicate on Workflow retry
            $idempotentKey = $payload->subjectType === 'SurveyResponse'
                ? md5("{$orgId}|workflow|survey|{$payload->subjectId}")
                : md5("{$orgId}|workflow|{$step->id}|" . ($payload->subjectId ?? 'none'));

            $data = StoreLeadData::from([
                'contact_name'    => $payload->extra['contact_name']    ?? ($payload->actorName ?? 'Khách từ Workflow'),
                'contact_phone'   => $payload->extra['contact_phone']   ?? null,
                'contact_email'   => $payload->extra['contact_email']   ?? ($payload->actorEmail ?? null),
                'contact_company' => $payload->extra['contact_company'] ?? null,
                'stage_id'        => $targetStage->id,
                'source_id'       => $source?->id,
                'source_detail'   => "Workflow: " . ($step->workflow?->name ?? $step->id),
                'assigned_to'     => $step->assigned_to ?? null,
                'title'           => $payload->extra['lead_title'] ?? null,
                'description'     => $payload->extra['lead_description'] ?? null,
                'currency'        => 'VND',
                'survey_response_id' => $payload->subjectType === 'SurveyResponse' ? $payload->subjectId : null,
                'survey_band_code'   => $payload->extra['band_code']     ?? null,
                'survey_score'       => $payload->extra['overall_score'] ?? null,
                'idempotent_key'     => $idempotentKey,
            ]);

            $lead = CreateLeadAction::run($data, $orgId);

            return ActionResult::ok($this->ms($start), ['lead_id' => $lead->id]);

        } catch (\Illuminate\Validation\ValidationException $e) {
            // Idempotent: if unique constraint hit, it means lead already exists — success
            if (str_contains($e->getMessage(), 'idempotent_key')) {
                return ActionResult::ok($this->ms($start), ['skipped' => 'duplicate']);
            }
            return ActionResult::fail($e->getMessage(), $this->ms($start));
        } catch (\Throwable $e) {
            return ActionResult::fail($e->getMessage(), $this->ms($start));
        }
    }

    private function ms(float $start): int
    {
        return (int) ((microtime(true) - $start) * 1000);
    }
}
