<?php

namespace Modules\ContentBrief\Events;

use Modules\ContentBrief\Models\ContentBriefGeneration;

class BriefGenerationCompleted
{
    public function __construct(public readonly ContentBriefGeneration $generation) {}
}
