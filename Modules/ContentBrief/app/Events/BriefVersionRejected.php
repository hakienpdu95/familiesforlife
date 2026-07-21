<?php

namespace Modules\ContentBrief\Events;

use Modules\ContentBrief\Models\ContentBriefVersion;

class BriefVersionRejected
{
    public function __construct(
        public readonly ContentBriefVersion $rejectedVersion,
        public readonly ContentBriefVersion $newDraftVersion,
    ) {}
}
