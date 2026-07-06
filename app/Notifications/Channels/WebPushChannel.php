<?php

namespace App\Notifications\Channels;

use App\Models\User;
use App\Services\WebPushService;
use Illuminate\Notifications\Notification;

class WebPushChannel
{
    public function __construct(private readonly WebPushService $service) {}

    public function send(mixed $notifiable, Notification $notification): void
    {
        if (!($notifiable instanceof User)) return;
        if (!method_exists($notification, 'toWebPush')) return;
        if (!$this->service->isConfigured()) return;

        $payload = $notification->toWebPush($notifiable, $notification);
        if (empty($payload)) return;

        $this->service->sendToUser($notifiable, $payload);
    }
}
