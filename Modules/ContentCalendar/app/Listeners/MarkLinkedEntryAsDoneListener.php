<?php

namespace Modules\ContentCalendar\Listeners;

use Modules\ContentCalendar\Enums\CalendarEntryStatus;
use Modules\ContentCalendar\Models\ContentCalendarEntry;
use Modules\Post\Features\ArticleAuthoring\Events\ArticlePublished;

/**
 * spec/ContentCalendar_Technical_Specification.md §5.3.2 — `Done` KHÔNG bao giờ set qua
 * ChangeCalendarEntryStatusAction (API công khai) — chỉ set ở đây, đúng lúc bài viết liên kết
 * thật sự chuyển Published.
 */
class MarkLinkedEntryAsDoneListener
{
    public function handle(ArticlePublished $event): void
    {
        // article_id, không phải translation_id — post_article_id trỏ tới BÀI VIẾT (khái niệm đa
        // ngôn ngữ), không tới 1 bản dịch cụ thể — publish BẤT KỲ bản dịch nào của bài đã liên kết
        // cũng coi là "xong" (đúng ngữ nghĩa displayStatusLabel() đang đọc mainTranslation()).
        //
        // Query Builder ->update() (không phải Eloquent save() từng model) — bỏ qua model event
        // (không chạy LogsActivity). Chấp nhận được: cập nhật hệ thống hàng loạt, không phải hành
        // động biên tập viên cần audit trail cá nhân — nếu sau này cần thấy "khi nào entry chuyển
        // Done" trong lịch sử, đổi sang lặp get()->each->update() (có event) khi có nhu cầu thật.
        ContentCalendarEntry::where('post_article_id', $event->translation->article_id)
            ->where('status', '!=', CalendarEntryStatus::Done->value)
            ->update(['status' => CalendarEntryStatus::Done->value]);
    }
}
