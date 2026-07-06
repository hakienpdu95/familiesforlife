<?php

namespace Modules\Recruitment\Actions\Backend;

use App\Shared\Tenancy\TenantContext;
use Lorisleiva\Actions\Concerns\AsAction;
use Modules\Recruitment\Data\Requests\StoreApplicationData;
use Modules\Recruitment\Models\RcApplication;
use Modules\Recruitment\Models\RcApplicationAnswer;
use Modules\Recruitment\Models\RcApplicationStageLog;

class StoreApplicationAction
{
    use AsAction;

    public function handle(StoreApplicationData $data): RcApplication
    {
        $orgId = TenantContext::getOrganizationId();

        $application = RcApplication::create([
            'org_id'             => $orgId,
            'candidate_id'       => $data->candidate_id,
            'current_stage_id'   => $data->stage_id,
            'jp_job_post_id'     => $data->jp_job_post_id,
            'apply_source'       => $data->apply_source,
            'cover_letter'       => $data->cover_letter,
            'expected_salary'    => $data->expected_salary,
            'notice_period_days' => $data->notice_period_days,
            'status'             => 'active',
        ]);

        // Xử lý screening answers + kiểm tra disqualify (BR-RC-002)
        if (!empty($data->answers)) {
            $disqualifyingAnswers = [];

            foreach ($data->answers as $answer) {
                $isDisq = !empty($answer['is_disqualifying']);

                RcApplicationAnswer::create([
                    'application_id'  => $application->id,
                    'jp_question_id'  => $answer['jp_question_id'],
                    'question_text'   => $answer['question_text'],
                    'question_type'   => $answer['question_type'],
                    'answer_text'     => $answer['answer_text'] ?? null,
                    'answer_bool'     => isset($answer['answer_bool']) ? (bool) $answer['answer_bool'] : null,
                    'answer_choices'  => $answer['answer_choices'] ?? null,
                    'is_disqualifying'=> $isDisq,
                ]);

                if ($isDisq) {
                    $disqualifyingAnswers[] = $answer['question_text'];
                }
            }

            if (!empty($disqualifyingAnswers)) {
                $application->update([
                    'is_disqualified'  => true,
                    'disqualify_reason'=> implode('; ', $disqualifyingAnswers),
                ]);
            }
        }

        RcApplicationStageLog::create([
            'application_id' => $application->id,
            'stage_id'       => $data->stage_id,
            'result'         => 'passed',
            'note'           => 'Tiếp nhận hồ sơ',
            'actioned_by'    => auth()->id(),
        ]);

        return $application;
    }
}
