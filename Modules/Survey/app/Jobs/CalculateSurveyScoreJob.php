<?php

namespace Modules\Survey\Jobs;

use Illuminate\Bus\Batchable;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldBeUnique;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Modules\Survey\Actions\CalculateSurveyScoreAction;

class CalculateSurveyScoreJob implements ShouldQueue, ShouldBeUnique
{
    use Batchable, Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 3;
    public array $backoff = [10, 60, 300];

    public function __construct(
        public readonly int  $responseId,
        public readonly bool $force = false,
    ) {
        $this->onQueue('high');
    }

    // Prevent duplicate scoring jobs for the same response.
    // Uniqueness is disabled inside a batch (bulk reprocess) to allow forced re-scoring.
    public function uniqueId(): string
    {
        return $this->batch() !== null ? '' : (string) $this->responseId;
    }

    public function handle(CalculateSurveyScoreAction $action): void
    {
        $action->handle($this->responseId, $this->force);
    }
}
