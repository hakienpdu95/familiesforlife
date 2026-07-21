<?php

namespace Modules\ContentBrief\Events;

use Modules\ContentBrief\Models\ContentBriefGeneration;

class BriefGenerationRequested
{
    public function __construct(public readonly ContentBriefGeneration $generation) {}
}
