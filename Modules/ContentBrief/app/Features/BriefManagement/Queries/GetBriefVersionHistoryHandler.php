<?php

namespace Modules\ContentBrief\Features\BriefManagement\Queries;

use App\Shared\Contracts\QueryHandlerInterface;
use App\Shared\Contracts\QueryInterface;
use Illuminate\Support\Collection;
use Modules\ContentBrief\Models\ContentBriefVersion;

class GetBriefVersionHistoryHandler implements QueryHandlerInterface
{
    /** Danh sách version mới → cũ, mỗi version kèm diff tóm tắt so với version liền trước (§4.2). */
    public function handle(QueryInterface $query): Collection
    {
        /** @var GetBriefVersionHistoryQuery $query */
        $versions = ContentBriefVersion::where('content_brief_id', $query->contentBriefId)
            ->with(['createdBy:id,name', 'submittedBy:id,name', 'approvedBy:id,name', 'generations'])
            ->orderByDesc('version_number')
            ->get();

        return $versions->map(function (ContentBriefVersion $version) use ($versions) {
            $previous = $versions->firstWhere('version_number', $version->version_number - 1);

            $version->setAttribute(
                'diff_against_previous',
                $previous ? SnapshotDiffer::diff($previous->snapshot, $version->snapshot) : []
            );

            return $version;
        });
    }
}
