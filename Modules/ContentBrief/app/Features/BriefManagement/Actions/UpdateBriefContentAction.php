<?php

namespace Modules\ContentBrief\Features\BriefManagement\Actions;

use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
use Lorisleiva\Actions\Concerns\AsAction;
use Modules\ContentBrief\Enums\BriefVersionStatus;
use Modules\ContentBrief\Enums\BriefVersionTrigger;
use Modules\ContentBrief\Features\BriefManagement\Actions\Concerns\GeneratesBriefVersions;
use Modules\ContentBrief\Features\BriefManagement\Data\BriefSnapshotData;
use Modules\ContentBrief\Models\ContentBrief;
use Modules\ContentBrief\Models\ContentBriefVersion;

class UpdateBriefContentAction
{
    use AsAction;
    use GeneratesBriefVersions;

    /**
     * spec/ContentBrief_Technical_Specification.md §3.3/§3.5/§3.7 — tạo version mới CHỈ NẾU
     * content_hash khác version hiện tại (no-op nếu giống hệt). Chặn cứng khi version hiện tại
     * đã approved — phải qua RestoreBriefVersionAction.
     */
    public function handle(ContentBrief $brief, BriefSnapshotData $snapshot): ContentBriefVersion
    {
        throw_if(
            $brief->currentVersion?->status === BriefVersionStatus::Approved,
            ValidationException::withMessages([
                'snapshot' => 'Version này đã được duyệt — không thể sửa trực tiếp. Hãy tạo bản nháp mới từ version này.',
            ])
        );

        return DB::transaction(function () use ($brief, $snapshot) {
            $brief = ContentBrief::whereKey($brief->id)->lockForUpdate()->first();
            $userId = auth()->id();

            $snapshotArray = $snapshot->toArray();
            $snapshotArray['schema_version'] = ContentBriefVersion::CURRENT_SCHEMA_VERSION;
            $hash = $this->hashSnapshot($snapshotArray);

            $latest = $brief->versions()->first();

            if ($latest && $latest->content_hash === $hash) {
                return $latest; // no-op — nội dung không đổi
            }

            $version = ContentBriefVersion::create([
                'content_brief_id' => $brief->id,
                'organization_id'  => $brief->organization_id,
                'version_number'   => $this->nextVersionNumber($brief),
                'status'           => BriefVersionStatus::Draft,
                'snapshot'         => $snapshotArray,
                'content_hash'     => $hash,
                'trigger'          => BriefVersionTrigger::Edited,
                'created_by'       => $userId,
            ]);

            $brief->update([
                'current_version_id' => $version->id,
                'target_keyword'      => $snapshotArray['target_keyword'],
                'category_label'      => $snapshotArray['suggested_category'] ?? null,
                'status'               => BriefVersionStatus::Draft,
                'updated_by'           => $userId,
            ]);

            return $version;
        });
    }
}
