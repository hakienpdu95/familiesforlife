<?php

namespace Modules\Event\Features\EventModeration\Actions;

use App\Models\User;
use Illuminate\Support\Facades\Notification;
use Lorisleiva\Actions\Concerns\AsAction;
use Modules\Event\Features\EventModeration\Notifications\EventSubmittedNotification;
use Modules\Event\Models\Event;

/**
 * spec/Event_Management_Technical_Specification.md §11.2 — báo platform_content_editor/head
 * khi có sự kiện mới nộp qua form public. Gọi từ SubmitEventAction, KHÔNG gọi từ
 * CreateEventAction (staff tự tạo thì chính họ đã biết, không cần tự báo cho mình).
 */
class NotifyEditorsOfNewSubmissionAction
{
    use AsAction;

    public function handle(Event $event): void
    {
        $recipients = User::withGlobalRole(['platform_content_editor', 'platform_content_head']);

        if ($recipients->isNotEmpty()) {
            Notification::send($recipients, new EventSubmittedNotification($event));
        }
    }
}
