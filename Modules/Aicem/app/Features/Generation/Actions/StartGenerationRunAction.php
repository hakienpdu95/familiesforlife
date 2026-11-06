<?php

namespace Modules\Aicem\Features\Generation\Actions;

use App\Models\AiSetting;
use Illuminate\Database\Eloquent\Model;
use Lorisleiva\Actions\Concerns\AsAction;
use Modules\Aicem\Enums\GenerationRunStatus;
use Modules\Aicem\Features\Generation\Jobs\RunAicemWorkflowJob;
use Modules\Aicem\Models\AicemGenerationRun;
use Modules\Aicem\Models\AicemWorkflow;

/**
 * Tạo AicemGenerationRun(status: pending) rồi dispatch job nền — không gọi AI đồng bộ trong
 * request/response cycle (mục 3: LLM call có thể mất 5-30s).
 */
class StartGenerationRunAction
{
    use AsAction;

    public function handle(Model $subject, string $subjectType, AicemWorkflow $workflow, int $userId): AicemGenerationRun
    {
        $providerConfig = AiSetting::providerConfig();

        $run = AicemGenerationRun::create([
            'subject_type'    => $subjectType,
            'subject_id'      => $subject->id,
            'workflow_id'     => $workflow->id,
            'requested_by'    => $userId,
            'provider'        => $providerConfig['provider'],
            'model'           => $providerConfig['model'],
            'status'          => GenerationRunStatus::Pending,
        ]);

        RunAicemWorkflowJob::dispatch($run->id);

        return $run;
    }
}
