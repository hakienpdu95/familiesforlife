<?php

namespace Modules\ContentBrief\Events;

use Modules\ContentBrief\Models\ContentBriefVersion;

class BriefVersionApproved
{
    public function __construct(public readonly ContentBriefVersion $version) {}
}
