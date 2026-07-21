<?php

namespace Modules\ContentBrief\Features\BriefManagement\Actions;

use Lorisleiva\Actions\Concerns\AsAction;
use Modules\ContentBrief\Enums\BriefVersionStatus;
use Modules\ContentBrief\Models\ContentBrief;

class ArchiveBriefAction
{
    use AsAction;

    /** spec/ContentBrief_Technical_Specification.md §3.8 — lựa chọn thay thế cho xoá. */
    public function handle(ContentBrief $brief): ContentBrief
    {
        $brief->update([
            'status'     => BriefVersionStatus::Archived,
            'updated_by' => auth()->id(),
        ]);

        return $brief;
    }
}
