<?php

namespace Modules\Survey\Jobs;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Modules\Survey\Models\SurveyResponse;

/**
 * GDPR hard-delete: permanently removes survey_responses (and cascaded rows)
 * that have been soft-deleted for more than 30 days.
 *
 * Runs daily via the scheduler. Processes in chunks of 200 to avoid lock contention.
 */
class PurgeDeletedResponsesJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries   = 1;
    public int $timeout = 300;

    public function __construct()
    {
        $this->onQueue('low');
    }

    public function handle(): void
    {
        $cutoff = now()->subDays(30);
        $total  = 0;

        SurveyResponse::onlyTrashed()
            ->where('deleted_at', '<', $cutoff)
            ->select('id')
            ->chunkById(200, function ($responses) use (&$total) {
                $ids = $responses->pluck('id')->all();

                DB::transaction(function () use ($ids) {
                    DB::table('survey_answers')->whereIn('response_id', $ids)->delete();
                    // assessment_results dùng polymorphic subject_type/subject_id (không phải
                    // response_id) — children (domain_scores, recommendations...) tự xóa theo
                    // FK cascade khi xóa dòng assessment_results.
                    DB::table('assessment_results')
                        ->where('subject_type', SurveyResponse::class)
                        ->whereIn('subject_id', $ids)
                        ->delete();
                    DB::table('submission_behavior_log')->whereIn('response_id', $ids)->delete();
                    SurveyResponse::onlyTrashed()->whereIn('id', $ids)->forceDelete();
                });

                $total += count($ids);
            });

        if ($total > 0) {
            Log::info('survey.gdpr.purge_completed', ['purged' => $total, 'cutoff' => $cutoff->toDateString()]);
        }
    }
}
