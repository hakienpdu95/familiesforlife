<?php

namespace Modules\ContentBrief\Events;

use Modules\ContentBrief\Models\ContentBriefVersion;

/** spec/ContentBrief_Technical_Specification.md §3.11 — domain event khuyến nghị, không bắt buộc. */
class BriefSubmittedForReview
{
    public function __construct(public readonly ContentBriefVersion $version) {}
}
