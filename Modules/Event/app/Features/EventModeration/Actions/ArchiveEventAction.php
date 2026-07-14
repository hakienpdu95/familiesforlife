<?php

namespace Modules\Event\Features\EventModeration\Actions;

use Lorisleiva\Actions\Concerns\AsAction;
use Modules\Event\Enums\EventStatus;
use Modules\Event\Models\Event;

/**
 * platform_content_head — gỡ thủ công: Approved|Published|Expired → Archived (terminal, spec §6).
 * Dùng khi phát hiện spam sau khi đã duyệt/xuất bản, trùng lặp, vi phạm chính sách.
 */
class ArchiveEventAction
{
    use AsAction;

    public function handle(Event $event): Event
    {
        abort_unless(
            $event->status->canTransitionTo(EventStatus::Archived),
            422,
            "Không thể lưu trữ sự kiện đang ở trạng thái \"{$event->status->label()}\"."
        );

        $event->update(['status' => EventStatus::Archived]);

        return $event;
    }
}
