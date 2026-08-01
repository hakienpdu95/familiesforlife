<?php

namespace Modules\ContentCalendar\Features\CalendarPlanning\Actions;

use Lorisleiva\Actions\Concerns\AsAction;
use Modules\ContentCalendar\Models\ContentCalendarEntry;
use Modules\Post\Models\PostCategory;

/**
 * spec/ContentCalendar_Technical_Specification.md §10 — đối xứng
 * Modules\CoreIdeaExtractor\Features\CategoryFoundation\Actions\ListCategoryExistingArticlesAction
 * (bài ĐÃ publish) — action này trả tiêu đề đang LÊN KẾ HOẠCH nhưng CHƯA publish, để tránh AI đề
 * xuất trùng ý tưởng đang được viết. Phase 1: chỉ đảm bảo endpoint tồn tại + đúng permission,
 * KHÔNG sửa CoreIdeaExtractor để tự gọi (Phase 2, xem spec §15/§16).
 */
class ListCategoryPlannedTitlesAction
{
    use AsAction;

    /** @return string[] Tiêu đề entry đang hoạt động (chưa Done/Dropped), mới nhất trước. */
    public function handle(PostCategory $category): array
    {
        $maxTitles      = (int) config('content_calendar.dedup.max_titles', 30);
        $dbFetchLimit   = (int) config('content_calendar.dedup.db_fetch_limit', 100);
        $activeStatuses = config('content_calendar.dedup.active_statuses', ['idea', 'planned', 'drafting', 'blocked', 'ready']);

        return ContentCalendarEntry::query()
            ->where('post_category_id', $category->id)
            ->whereIn('status', $activeStatuses)
            ->latest('created_at')
            ->limit($dbFetchLimit)
            ->pluck('title')
            ->take($maxTitles)
            ->values()
            ->all();
    }
}
