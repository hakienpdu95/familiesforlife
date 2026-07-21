<?php

namespace Modules\ContentBrief\Features\BriefManagement\Actions;

use Illuminate\Support\Facades\DB;
use Lorisleiva\Actions\Concerns\AsAction;
use Modules\ContentBrief\Enums\BriefVersionStatus;
use Modules\ContentBrief\Enums\BriefVersionTrigger;
use Modules\ContentBrief\Features\BriefManagement\Actions\Concerns\GeneratesBriefVersions;
use Modules\ContentBrief\Features\BriefManagement\Data\BriefSnapshotData;
use Modules\ContentBrief\Models\ContentBrief;
use Modules\ContentBrief\Models\ContentBriefVersion;

class CreateBriefAction
{
    use AsAction;
    use GeneratesBriefVersions;

    /**
     * spec/ContentBrief_Technical_Specification.md §3.5 — tạo ContentBrief + version 1
     * (trigger=created), stamp schema_version trước khi hash.
     *
     * @param array{title: string, assigned_to?: int|null} $briefAttrs
     */
    public function handle(array $briefAttrs, BriefSnapshotData $snapshot): ContentBrief
    {
        return DB::transaction(function () use ($briefAttrs, $snapshot) {
            $userId = auth()->id();

            $brief = ContentBrief::create([
                'title'          => $briefAttrs['title'],
                'target_keyword' => $snapshot->target_keyword,
                'category_label' => $snapshot->suggested_category,
                'assigned_to'    => $briefAttrs['assigned_to'] ?? null,
                'status'         => BriefVersionStatus::Draft,
                'created_by'     => $userId,
            ]);

            $snapshotArray = $snapshot->toArray();
            $snapshotArray['schema_version'] = ContentBriefVersion::CURRENT_SCHEMA_VERSION;

            $version = ContentBriefVersion::create([
                'content_brief_id' => $brief->id,
                'organization_id'  => $brief->organization_id,
                'version_number'   => 1,
                'status'           => BriefVersionStatus::Draft,
                'snapshot'         => $snapshotArray,
                'content_hash'     => $this->hashSnapshot($snapshotArray),
                'trigger'          => BriefVersionTrigger::Created,
                'created_by'       => $userId,
            ]);

            $brief->update(['current_version_id' => $version->id]);

            return $brief->fresh();
        });
    }
}
