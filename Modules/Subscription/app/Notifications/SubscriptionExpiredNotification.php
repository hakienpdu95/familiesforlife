<?php

namespace Modules\Subscription\Notifications;

use App\Notifications\Concerns\RespectsNotificationPreferences;
use App\Shared\Notifications\NotificationData;
use App\Shared\Tenancy\Models\Organization;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;
use Laravelcm\Subscriptions\Models\Subscription;

class SubscriptionExpiredNotification extends Notification implements ShouldQueue
{
    use Queueable, RespectsNotificationPreferences;

    public function __construct(
        public readonly Organization $organization,
        public readonly Subscription $subscription,
    ) {}

    protected function notificationType(): string { return 'subscription_expired_db'; }

    public function toDatabase(object $notifiable): array
    {
        $planName = $this->subscription->plan->name ?? 'subscription';

        return NotificationData::make(
            type:     'subscription_expired_db',
            title:    "Subscription đã hết hạn",
            body:     "Subscription {$planName} của {$this->organization->name} đã hết hạn. Một số tính năng có thể bị hạn chế.",
            url:      route('subscription.portal.billing'),
            icon:     'error',
            severity: 'error',
            meta:     [
                'organization_id' => $this->organization->id,
                'plan_name'       => $planName,
            ],
        );
    }

    public function toMail(object $notifiable): MailMessage
    {
        $planName = $this->subscription->plan->name ?? 'subscription';
        $orgName  = $this->organization->name;

        return (new MailMessage)
            ->subject("[{$orgName}] Subscription đã hết hạn")
            ->greeting("Xin chào {$notifiable->name},")
            ->line("Subscription **{$planName}** của **{$orgName}** đã hết hạn.")
            ->line('Một số tính năng của bạn có thể bị hạn chế. Vui lòng gia hạn để khôi phục truy cập đầy đủ.')
            ->action('Gia hạn ngay', route('subscription.portal.billing'))
            ->line('Cảm ơn bạn đã tin dùng dịch vụ của chúng tôi.');
    }
}
