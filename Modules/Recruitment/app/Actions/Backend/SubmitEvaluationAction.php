<?php

namespace Modules\Recruitment\Actions\Backend;

use Lorisleiva\Actions\Concerns\AsAction;
use Modules\Recruitment\Models\RcEvaluationCriterion;
use Modules\Recruitment\Models\RcInterview;
use Modules\Recruitment\Models\RcInterviewEvaluation;

class SubmitEvaluationAction
{
    use AsAction;

    /**
     * @param  array{
     *   overall_score: int,
     *   verdict: string,
     *   strengths: ?string,
     *   weaknesses: ?string,
     *   recommendation: ?string,
     *   criteria: array<array{criterion_name: string, score: int, comment: ?string}>,
     * } $data
     */
    public function handle(RcInterview $interview, array $data): RcInterviewEvaluation
    {
        $evaluatorId = auth()->id();

        // Upsert: cho phép lưu draft rồi submit sau
        $evaluation = RcInterviewEvaluation::firstOrNew([
            'interview_id' => $interview->id,
            'evaluator_id' => $evaluatorId,
        ]);

        $evaluation->fill([
            'overall_score'  => $data['overall_score'],
            'verdict'        => $data['verdict'],
            'strengths'      => $data['strengths'] ?? null,
            'weaknesses'     => $data['weaknesses'] ?? null,
            'recommendation' => $data['recommendation'] ?? null,
            'is_submitted'   => true,
            'submitted_at'   => now(),
        ]);
        $evaluation->save();

        // Thay thế toàn bộ criteria
        $evaluation->criteria()->delete();
        foreach ($data['criteria'] ?? [] as $c) {
            RcEvaluationCriterion::create([
                'evaluation_id'  => $evaluation->id,
                'criterion_name' => $c['criterion_name'],
                'score'          => $c['score'],
                'comment'        => $c['comment'] ?? null,
            ]);
        }

        return $evaluation->load('criteria');
    }
}
