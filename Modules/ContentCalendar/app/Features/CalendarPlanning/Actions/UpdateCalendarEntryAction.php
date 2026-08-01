<?php

namespace Modules\ContentCalendar\Features\CalendarPlanning\Actions;

use App\Models\User;
use Lorisleiva\Actions\Concerns\AsAction;
use Modules\ContentCalendar\Features\CalendarPlanning\Actions\Concerns\EnforcesCategoryScope;
use Modules\ContentCalendar\Features\CalendarPlanning\Data\CalendarEntryData;
use Modules\ContentCalendar\Models\ContentCalendarEntry;

/**
 * spec/ContentCalendar_Technical_Specification.md §6.4/§5.3.1 — sửa metadata của entry
 * (title/brief/assigned_to/target_publish_date/category). KHÔNG ghi cột `status` — đó là việc
 * riêng của ChangeCalendarEntryStatusAction (nơi DUY NHẤT được phép đổi status, §7). Khoá sau khi
 * liên kết PostArticle (§5.3.1) chỉ áp dụng cho `status`, các field ở đây vẫn sửa được bình
 * thường kể cả khi đã liên kết (§5.3.1 — "khoá chỉ áp dụng cho cột status").
 */
class UpdateCalendarEntryAction
{
    use AsAction;
    use EnforcesCategoryScope;

    public function handle(ContentCalendarEntry $entry, User $actor, CalendarEntryData $data): ContentCalendarEntry
    {
        // Chỉ re-check phạm vi category khi THỰC SỰ đổi category — tránh chặn nhầm 1 section_editor
        // đang sửa field khác (vd đổi ngày) của 1 entry mà category không đổi.
        if ($data->post_category_id !== $entry->post_category_id) {
            $this->assertCategoryInScope($actor, $data->post_category_id);
        }

        $entry->update([
            'post_category_id'    => $data->post_category_id,
            'title'               => $data->title,
            'brief'               => $data->brief,
            'origin'              => $data->origin,
            'origin_note'         => $data->origin_note,
            'target_publish_date' => $data->target_publish_date,
            'assigned_to'         => $data->assigned_to,
            'updated_by'          => $actor->id,
        ]);

        return $entry->refresh();
    }
}
