<?php

namespace Modules\Assessment\Actions;

use Illuminate\Support\Facades\Log;
use Lorisleiva\Actions\Concerns\AsAction;
use Modules\Assessment\Contracts\ScoringSubjectInterface;
use Modules\Assessment\Engine\ScoringEngineService;
use Modules\Assessment\Events\AssessmentCompleted;
use Modules\Assessment\Events\AssessmentFailed;
use Modules\Assessment\Models\AssessmentResult;

class RunAssessmentAction
{
    use AsAction;

    public string $jobQueue   = 'high';
    public int    $jobTries   = 3;
    public array  $jobBackoff = [10, 60, 300];

    public function __construct(
        private readonly ScoringEngineService $engine,
    ) {}

    public function handle(ScoringSubjectInterface $subject, bool $force = false): AssessmentResult
    {
        if (!$subject->getAssessmentCode()) {
            Log::info('assessment.skipped.no_code', [
                'subject_type' => $subject->getScoringSubjectType(),
                'subject_id'   => $subject->getScoringSubjectId(),
            ]);
            abort(422, 'Subject does not have an assessment code.');
        }

        try {
            $scoringResult = $this->engine->calculate($subject, $force);
        } catch (\Throwable $e) {
            event(new AssessmentFailed($subject, $e));
            Log::error('assessment.failed', [
                'subject_type'    => $subject->getScoringSubjectType(),
                'subject_id'      => $subject->getScoringSubjectId(),
                'assessment_code' => $subject->getAssessmentCode(),
                'error'           => $e->getMessage(),
            ]);
            throw $e;
        }

        $result = AssessmentResult::forSubject(
            $subject->getScoringSubjectType(),
            $subject->getScoringSubjectId(),
        )->first();

        if ($result === null) {
            // Engine không persist (vd. assessment config chưa bật has_scoring) — báo lỗi rõ ràng
            // thay vì để ModelNotFoundException mơ hồ lọt ra ngoài.
            abort(422, 'Assessment chưa được cấu hình chấm điểm (has_scoring=false hoặc thiếu config).');
        }

        event(new AssessmentCompleted($result, $scoringResult, $subject));

        return $result;
    }

    public function asJob(ScoringSubjectInterface $subject, bool $force = false): void
    {
        $this->handle($subject, $force);
    }
}
