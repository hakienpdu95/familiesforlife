<?php

namespace Modules\Assessment\Jobs;

use Illuminate\Bus\Batchable;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldBeUnique;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Modules\Assessment\Actions\RunAssessmentAction;
use Modules\Assessment\Contracts\ScoringSubjectInterface;

class RunAssessmentJob implements ShouldQueue, ShouldBeUnique
{
    use Batchable, Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int   $tries   = 3;
    public array $backoff = [10, 60, 300];

    public function __construct(
        public readonly ScoringSubjectInterface $subject,
        public readonly bool                    $force = false,
    ) {
        $this->onQueue('high');
    }

    public function uniqueId(): string
    {
        return $this->batch() !== null
            ? ''
            : $this->subject->getScoringSubjectType() . ':' . $this->subject->getScoringSubjectId();
    }

    public function handle(RunAssessmentAction $action): void
    {
        $action->handle($this->subject, $this->force);
    }
}
