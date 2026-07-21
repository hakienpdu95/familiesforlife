<?php

namespace Modules\ContentBrief\Features\BriefManagement\Actions;

use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
use Lorisleiva\Actions\Concerns\AsAction;
use Modules\ContentBrief\Enums\BriefVersionStatus;
use Modules\ContentBrief\Enums\BriefVersionTrigger;
use Modules\ContentBrief\Events\BriefVersionRejected;
use Modules\ContentBrief\Features\BriefManagement\Actions\Concerns\GeneratesBriefVersions;
use Modules\ContentBrief\Models\ContentBrief;
use Modules\ContentBrief\Models\ContentBriefVersion;

class RejectBriefVersionAction
{
    use AsAction;
    use GeneratesBriefVersions;

    /**
     * spec/ContentBrief_Technical_Specification.md §3.9 — điểm dễ implement sai nhất: version
     * bị từ chối GIỮ NGUYÊN trong lịch sử (không sửa snapshot), nhưng content_briefs.
     * current_version_id/status BẮT BUỘC được đồng bộ sang version draft mới tạo, trong CÙNG 1
     * transaction — nếu không, danh sách/currentVersion vẫn hiện "rejected" dù đã có bản draft
     * mới sẵn sàng sửa tiếp.
     */
    public function handle(ContentBrief $brief, string $reason): ContentBriefVersion
    {
        return DB::transaction(function () use ($brief, $reason) {
            $lockedBrief = ContentBrief::whereKey($brief->id)->lockForUpdate()->first();
            $rejectedVersion = ContentBriefVersion::whereKey($lockedBrief->current_version_id)->lockForUpdate()->first();

            throw_if($rejectedVersion->status !== BriefVersionStatus::InReview, ValidationException::withMessages([
                'status' => 'Chỉ có thể từ chối version đang chờ duyệt.',
            ]));

            $userId = auth()->id();

            // 1. Version bị từ chối GIỮ NGUYÊN trong lịch sử, chỉ đổi status/rejected_reason.
            $rejectedVersion->update([
                'status'          => BriefVersionStatus::Rejected,
                'rejected_reason' => $reason,
            ]);

            // 2. Tạo 1 version MỚI (không sửa lại version vừa reject) — snapshot giữ nguyên nội
            //    dung bị từ chối để người soạn sửa tiếp từ đó, không phải gõ lại từ đầu.
            $newDraft = ContentBriefVersion::create([
                'content_brief_id' => $lockedBrief->id,
                'organization_id'  => $lockedBrief->organization_id,
                'version_number'   => $this->nextVersionNumber($lockedBrief),
                'status'           => BriefVersionStatus::Draft,
                'snapshot'         => $rejectedVersion->snapshot,
                'content_hash'     => $rejectedVersion->content_hash,
                'trigger'          => BriefVersionTrigger::Edited,
                'created_by'       => $userId,
            ]);

            // 3. BẮT BUỘC — bước hay bị bỏ sót nhất.
            $lockedBrief->update([
                'current_version_id' => $newDraft->id,
                'status'              => BriefVersionStatus::Draft,
                'updated_by'          => $userId,
            ]);

            event(new BriefVersionRejected($rejectedVersion, $newDraft));

            return $newDraft;
        });
    }
}
