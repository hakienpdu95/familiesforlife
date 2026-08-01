<?php

namespace Modules\ContentCalendar\Features\CalendarPlanning\Actions;

use App\Models\User;
use Lorisleiva\Actions\Concerns\AsAction;
use Modules\ContentCalendar\Enums\CalendarEntryStatus;
use Modules\ContentCalendar\Features\CalendarPlanning\Actions\Concerns\EnforcesCategoryScope;
use Modules\ContentCalendar\Features\CalendarPlanning\Data\CalendarEntryData;
use Modules\ContentCalendar\Models\ContentCalendarEntry;

/**
 * spec/ContentCalendar_Technical_Specification.md §6.4 — Policy::create() chỉ trả lời "được tạo
 * entry NÓI CHUNG không" (content_calendar.manage); phạm vi category cụ thể cho
 * platform_section_editor PHẢI được kiểm tra ở đây (Action), không tin form client.
 */
class CreateCalendarEntryAction
{
    use AsAction;
    use EnforcesCategoryScope;

    public function handle(User $actor, CalendarEntryData $data): ContentCalendarEntry
    {
        $this->assertCategoryInScope($actor, $data->post_category_id);

        // `status` set TƯỜNG MINH ở đây (không dựa vào DB column default `idea`) — Eloquent
        // create() không tự đọc lại default DB cho attribute không được truyền vào, model trả về
        // sẽ có `status = null` nếu bỏ dòng này (cùng convention Modules\Post — luôn set status
        // tường minh khi ghi, không bao giờ dựa vào DB default để suy ra state trong PHP).
        return ContentCalendarEntry::create([
            'post_category_id'    => $data->post_category_id,
            'title'               => $data->title,
            'brief'               => $data->brief,
            'origin'              => $data->origin,
            'origin_note'         => $data->origin_note,
            'status'              => CalendarEntryStatus::Idea,
            'target_publish_date' => $data->target_publish_date,
            'assigned_to'         => $data->assigned_to,
            'created_by'          => $actor->id,
            'updated_by'          => $actor->id,
        ]);
    }
}
