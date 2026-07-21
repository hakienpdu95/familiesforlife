<?php

namespace Modules\ContentBrief\Features\BriefManagement\Actions;

use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
use Lorisleiva\Actions\Concerns\AsAction;
use Modules\ContentBrief\Enums\BriefVersionStatus;
use Modules\ContentBrief\Events\BriefVersionApproved;
use Modules\ContentBrief\Models\ContentBrief;
use Modules\ContentBrief\Models\ContentBriefVersion;

class ApproveBriefVersionAction
{
    use AsAction;

    /** spec/ContentBrief_Technical_Specification.md §3.3/§3.10 — KHÔNG tạo version mới. */
    public function handle(ContentBrief $brief): ContentBriefVersion
    {
        return DB::transaction(function () use ($brief) {
            $version = ContentBriefVersion::whereKey($brief->current_version_id)->lockForUpdate()->first();

            throw_if($version->status !== BriefVersionStatus::InReview, ValidationException::withMessages([
                'status' => 'Chỉ có thể duyệt version đang chờ duyệt.',
            ]));

            $version->update([
                'status'      => BriefVersionStatus::Approved,
                'approved_by' => auth()->id(),
                'approved_at' => now(),
            ]);

            $brief->update(['status' => BriefVersionStatus::Approved, 'updated_by' => auth()->id()]);

            event(new BriefVersionApproved($version));

            return $version;
        });
    }
}
