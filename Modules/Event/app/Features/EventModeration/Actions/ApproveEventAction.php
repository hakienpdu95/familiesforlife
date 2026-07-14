<?php

namespace Modules\Event\Features\EventModeration\Actions;

use Lorisleiva\Actions\Concerns\AsAction;
use Modules\Event\Enums\EventStatus;
use Modules\Event\Models\Event;

/** platform_content_editor/head — sơ duyệt: Submitted → Approved (spec §6). */
class ApproveEventAction
{
    use AsAction;

    public function handle(Event $event): Event
    {
        abort_unless(
            $event->status->canTransitionTo(EventStatus::Approved),
            422,
            "Không thể duyệt sự kiện đang ở trạng thái \"{$event->status->label()}\"."
        );

        $event->update([
            'status'      => EventStatus::Approved,
            'approved_by' => auth()->id(),
            'approved_at' => now(),
        ]);

        return $event;
    }
}
