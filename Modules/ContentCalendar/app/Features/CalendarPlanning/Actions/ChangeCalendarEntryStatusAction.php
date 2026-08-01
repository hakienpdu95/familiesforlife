<?php

namespace Modules\ContentCalendar\Features\CalendarPlanning\Actions;

use Illuminate\Validation\ValidationException;
use Lorisleiva\Actions\Concerns\AsAction;
use Modules\ContentCalendar\Enums\CalendarEntryStatus;
use Modules\ContentCalendar\Features\CalendarPlanning\Exceptions\InvalidTransitionException;
use Modules\ContentCalendar\Models\ContentCalendarEntry;

/**
 * spec/ContentCalendar_Technical_Specification.md §5.3.1/§7/§17.1 — nơi DUY NHẤT được phép ghi
 * cột `status`. `canTransitionTo()` của enum là đồ thị THUẦN, không biết gì về `post_article_id`
 * — 2 quy tắc khoá dưới đây áp dụng THÊM, không phải thay thế, đồ thị đó:
 *
 * 1. Nếu entry đã liên kết PostArticle (`post_article_id !== null`): chỉ chấp nhận target
 *    `Dropped`, mọi target khác bị từ chối bất kể đồ thị cho phép gì — tiến độ THẬT từ lúc đó do
 *    TranslationStatus quyết định (ContentCalendarEntry::displayStatusLabel()).
 * 2. `Done` KHÔNG BAO GIỜ là target hợp lệ qua Action này (loại trừ tường minh, không dựa vào
 *    canTransitionTo()) — chỉ MarkLinkedEntryAsDoneListener được set Done.
 */
class ChangeCalendarEntryStatusAction
{
    use AsAction;

    public function handle(ContentCalendarEntry $entry, CalendarEntryStatus $target): ContentCalendarEntry
    {
        $current = $entry->status;

        if ($target === CalendarEntryStatus::Done) {
            throw new InvalidTransitionException($current->value, $target->value);
        }

        if ($entry->isLinkedToArticle() && $target !== CalendarEntryStatus::Dropped) {
            throw new InvalidTransitionException($current->value, $target->value);
        }

        if (! $current->canTransitionTo($target)) {
            throw new InvalidTransitionException($current->value, $target->value);
        }

        // spec §17.1 — định nghĩa "Planned" là "đã có ngày dự kiến ĐĂNG + người viết", enforce cả
        // 2 vế (không chỉ ngày) để tên trạng thái không nói dối.
        if ($target === CalendarEntryStatus::Planned && (! $entry->target_publish_date || ! $entry->assigned_to)) {
            throw ValidationException::withMessages([
                'status' => 'Cần có ngày dự kiến đăng và người phụ trách trước khi chuyển sang Planned.',
            ]);
        }

        $entry->update(['status' => $target]);

        return $entry->refresh();
    }
}
