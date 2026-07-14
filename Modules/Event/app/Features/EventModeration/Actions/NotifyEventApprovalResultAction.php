<?php

namespace Modules\Event\Features\EventModeration\Actions;

use Illuminate\Support\Facades\Notification;
use Lorisleiva\Actions\Concerns\AsAction;
use Modules\Event\Features\EventModeration\Notifications\EventApprovalResultNotification;
use Modules\Event\Models\Event;

/**
 * spec/Event_Management_Technical_Specification.md §11.2 — chỉ gửi khi sự kiện có
 * EventSubmission (nộp qua form public). Sự kiện staff tự tạo trong dashboard không có dòng
 * này (quan hệ optional — spec §4) nên không có ai để báo, no-op im lặng.
 */
class NotifyEventApprovalResultAction
{
    use AsAction;

    public function handle(Event $event): void
    {
        $event->loadMissing('submission');

        $email = $event->submission?->submitter_email;

        if (! $email) {
            return;
        }

        Notification::route('mail', $email)->notify(new EventApprovalResultNotification($event));
    }
}
