<?php

namespace Modules\ContentCalendar\Features\CalendarPlanning\Actions;

use Illuminate\Validation\ValidationException;
use Lorisleiva\Actions\Concerns\AsAction;
use Modules\ContentCalendar\Enums\CalendarEntryStatus;
use Modules\ContentCalendar\Models\ContentCalendarEntry;
use Modules\Post\Models\PostArticle;

/**
 * spec/ContentCalendar_Technical_Specification.md §10/§17.3 — điểm liên kết chính thức duy nhất
 * giữa kế hoạch (ContentCalendar) và bài viết thật (Post). Validate TRƯỚC khi chạm ràng buộc UNIQUE
 * ở DB (content_calendar_entries.post_article_id) để trả lỗi rõ ràng thay vì QueryException 500.
 */
class LinkCalendarEntryToArticleAction
{
    use AsAction;

    public function handle(ContentCalendarEntry $entry, PostArticle $article): ContentCalendarEntry
    {
        if (ContentCalendarEntry::where('post_article_id', $article->id)->exists()) {
            throw ValidationException::withMessages([
                'post_article_id' => 'Bài viết này đã được gắn với 1 kế hoạch khác.',
            ]);
        }

        // Cùng kiểu check PostArticlePolicy::approve() dùng cho platform_section_editor (đối
        // chiếu category bài viết với category entry) — CHẶN CỨNG (ValidationException) nếu bài
        // viết không thuộc category của entry, với thông báo đủ rõ để editor tự sửa (đổi category
        // entry hoặc chọn bài khác) — chấp nhận GIAO nhau với category PHỤ của bài (không đòi
        // trùng category CHÍNH/primaryCategory()), vì 1 bài có thể gắn nhiều category.
        $article->loadMissing('categories');
        if (! $article->categories->pluck('id')->contains($entry->post_category_id)) {
            throw ValidationException::withMessages([
                'post_article_id' => 'Bài viết không thuộc category của kế hoạch này — kiểm tra lại trước khi gắn.',
            ]);
        }

        $entry->update(['post_article_id' => $article->id]);

        if (in_array($entry->status, [CalendarEntryStatus::Idea, CalendarEntryStatus::Planned], true)) {
            $entry->update(['status' => CalendarEntryStatus::Drafting]);
        }

        return $entry->refresh();
    }
}
