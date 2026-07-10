<?php

namespace Modules\Aicem\Features\Generation\Actions;

use Illuminate\Database\Eloquent\Model;
use Lorisleiva\Actions\Concerns\AsAction;
use Modules\Aicem\Enums\GenerationRunStatus;
use Modules\Aicem\Features\Generation\Jobs\RunAicemWorkflowJob;
use Modules\Aicem\Models\AicemGenerationRun;
use Modules\Aicem\Models\AicemWorkflow;

/**
 * Tạo AicemGenerationRun(status: pending) rồi dispatch job nền — không gọi AI đồng bộ trong
 * request/response cycle (mục 3: LLM call có thể mất 5-30s).
 *
 * organization_id LUÔN lấy từ $subject->organization_id (chủ sở hữu thật của bài viết/sản phẩm),
 * KHÔNG dựa vào TenantContext hiện tại của người bấm — vì super-admin (organization_id=NULL,
 * OrganizationScope bypass hoàn toàn) hoàn toàn có thể đang thao tác trên 1 subject thuộc
 * Organization KHÁC với "org hiện tại" (thường là org hệ thống mặc định). Nếu lấy theo
 * TenantContext ambient, job chạy nền sau đó sẽ tìm subject sai Organization → luôn báo
 * "không tồn tại" dù subject vẫn còn — xem RunAicemWorkflowJob.
 */
class StartGenerationRunAction
{
    use AsAction;

    public function handle(Model $subject, string $subjectType, AicemWorkflow $workflow, int $userId): AicemGenerationRun
    {
        $providerConfig = config('ai.default');

        $run = AicemGenerationRun::create([
            'organization_id' => $subject->organization_id,
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
