<?php

namespace Modules\Assessment\Events;

use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class HighDivergenceDetected
{
    use Dispatchable, SerializesModels;

    public function __construct(
        public readonly int $responseId,
        public readonly float $divergenceScore,
        public readonly ?int $subjectUserId = null,
    ) {}
}
