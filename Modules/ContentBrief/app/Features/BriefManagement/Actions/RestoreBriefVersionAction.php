<?php

namespace Modules\ContentBrief\Features\BriefManagement\Actions;

use Illuminate\Support\Facades\DB;
use Lorisleiva\Actions\Concerns\AsAction;
use Modules\ContentBrief\Enums\BriefVersionStatus;
use Modules\ContentBrief\Enums\BriefVersionTrigger;
use Modules\ContentBrief\Features\BriefManagement\Actions\Concerns\GeneratesBriefVersions;
use Modules\ContentBrief\Models\ContentBrief;
use Modules\ContentBrief\Models\ContentBriefVersion;

class RestoreBriefVersionAction
{
    use AsAction;
    use GeneratesBriefVersions;

    /**
     * spec/ContentBrief_Technical_Specification.md §3.3 — dùng khi cần sửa lại 1 version đã
     * approved, hoặc phục hồi 1 version cũ hơn bất kỳ. Luôn tạo version MỚI ở cuối chuỗi
     * (KHÔNG "tua ngược" số version), restored_from_version_id trỏ đúng version nguồn.
     */
    public function handle(ContentBrief $brief, ContentBriefVersion $sourceVersion): ContentBriefVersion
    {
        return DB::transaction(function () use ($brief, $sourceVersion) {
            $lockedBrief = ContentBrief::whereKey($brief->id)->lockForUpdate()->first();
            $userId = auth()->id();

            $newVersion = ContentBriefVersion::create([
                'content_brief_id'         => $lockedBrief->id,
                'organization_id'          => $lockedBrief->organization_id,
                'version_number'           => $this->nextVersionNumber($lockedBrief),
                'status'                   => BriefVersionStatus::Draft,
                'snapshot'                 => $sourceVersion->snapshot,
                'content_hash'             => $sourceVersion->content_hash,
                'trigger'                  => BriefVersionTrigger::Restored,
                'restored_from_version_id' => $sourceVersion->id,
                'created_by'               => $userId,
            ]);

            $lockedBrief->update([
                'current_version_id' => $newVersion->id,
                'target_keyword'      => $sourceVersion->snapshot['target_keyword'] ?? $lockedBrief->target_keyword,
                'category_label'      => $sourceVersion->snapshot['suggested_category'] ?? null,
                'status'               => BriefVersionStatus::Draft,
                'updated_by'           => $userId,
            ]);

            return $newVersion;
        });
    }
}
