<?php

namespace App\Notifications\Concerns;

use App\Services\NotificationPreferenceService;
use Illuminate\Notifications\Messages\BroadcastMessage;

/**
 * Apply this trait to any Notification class to have its via() method
 * respect the user's stored notification preferences.
 *
 * The notification class must implement notificationType() returning
 * the snake_case event type string (e.g. 'task_assigned').
 *
 * Defaults when no preference row exists:
 *   database  = on  (always)
 *   broadcast = on  (follows database)
 *   mail      = on  (only when the class defines toMail())
 *   webpush   = off (must be explicitly enabled by user)
 *
 * Class-level methods take precedence over these trait defaults, so
 * classes may still override toBroadcast() or toWebPush() individually.
 */
trait RespectsNotificationPreferences
{
    abstract protected function notificationType(): string;

    public function via(object $notifiable): array
    {
        return app(NotificationPreferenceService::class)->channelsFor(
            $notifiable,
            $this->notificationType(),
            method_exists($this, 'toMail'),
        );
    }

    public function toBroadcast(object $notifiable): BroadcastMessage
    {
        $data = $this->toDatabase($notifiable);
        $data['notification_type'] = $data['type'] ?? $this->notificationType();
        unset($data['type']);
        return new BroadcastMessage($data);
    }

    public function toWebPush(object $notifiable): array
    {
        return $this->toDatabase($notifiable);
    }
}
