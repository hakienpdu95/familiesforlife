<?php

namespace Modules\ContentBrief\Features\BriefManagement\Actions;

use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
use Lorisleiva\Actions\Concerns\AsAction;
use Modules\ContentBrief\Enums\BriefVersionStatus;
use Modules\ContentBrief\Events\BriefSubmittedForReview;
use Modules\ContentBrief\Models\ContentBrief;
use Modules\ContentBrief\Models\ContentBriefVersion;

class SubmitBriefForReviewAction
{
    use AsAction;

    /**
     * spec/ContentBrief_Technical_Specification.md §3.3/§3.10 — KHÔNG tạo version mới, chỉ đổi
     * status trên version hiện tại. lockForUpdate + guard chỉ cho phép từ status=Draft (chặn
     * gửi duyệt trùng lặp — AC §8).
     */
    public function handle(ContentBrief $brief): ContentBriefVersion
    {
        return DB::transaction(function () use ($brief) {
            $version = ContentBriefVersion::whereKey($brief->current_version_id)->lockForUpdate()->first();

            throw_if($version->status !== BriefVersionStatus::Draft, ValidationException::withMessages([
                'status' => 'Chỉ có thể gửi duyệt khi version đang ở trạng thái Nháp.',
            ]));

            $version->update([
                'status'       => BriefVersionStatus::InReview,
                'submitted_by' => auth()->id(),
                'submitted_at' => now(),
            ]);

            $brief->update(['status' => BriefVersionStatus::InReview, 'updated_by' => auth()->id()]);

            event(new BriefSubmittedForReview($version));

            return $version;
        });
    }
}
