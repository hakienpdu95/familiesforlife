<?php

namespace Modules\Event\Features\EventModeration\Actions;

use Lorisleiva\Actions\Concerns\AsAction;
use Modules\Event\Enums\EventStatus;
use Modules\Event\Models\Event;

/** platform_content_head — duyệt cuối + xuất bản: Approved → Published (spec §6). */
class PublishEventAction
{
    use AsAction;

    public function handle(Event $event): Event
    {
        abort_unless(
            $event->status->canTransitionTo(EventStatus::Published),
            422,
            "Không thể xuất bản sự kiện đang ở trạng thái \"{$event->status->label()}\"."
        );

        $event->update([
            'status'       => EventStatus::Published,
            'published_at' => now(),
        ]);

        return $event;
    }
}
