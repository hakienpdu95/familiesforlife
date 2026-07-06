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

class RenewalReminderNotification extends Notification implements ShouldQueue
{
    use Queueable, RespectsNotificationPreferences;

    public function __construct(
        public readonly Organization $organization,
        public readonly Subscription $subscription,
        public readonly int          $daysLeft,
    ) {}

    protected function notificationType(): string { return 'subscription_expiring_db'; }

    public function toDatabase(object $notifiable): array
    {
        $planName = $this->subscription->plan->name ?? 'subscription';
        $endsAt   = $this->subscription->ends_at?->toDateString();

        return NotificationData::make(
            type:     'subscription_expiring_db',
            title:    "Subscription sắp hết hạn — còn {$this->daysLeft} ngày",
            body:     "Subscription {$planName} của {$this->organization->name} sẽ hết hạn vào {$this->subscription->ends_at?->format('d/m/Y')}. Vui lòng gia hạn để không bị gián đoạn dịch vụ.",
            url:      route('subscription.portal.billing'),
            icon:     'warning',
            severity: 'warning',
            meta:     [
                'organization_id' => $this->organization->id,
                'plan_name'       => $planName,
                'days_left'       => $this->daysLeft,
                'ends_at'         => $endsAt,
            ],
        );
    }

    public function toMail(object $notifiable): MailMessage
    {
        $planName  = $this->subscription->plan->name ?? 'subscription';
        $endsAt    = $this->subscription->ends_at?->format('d/m/Y');
        $orgName   = $this->organization->name;

        return (new MailMessage)
            ->subject("[{$orgName}] Subscription sắp hết hạn trong {$this->daysLeft} ngày")
            ->greeting("Xin chào {$notifiable->name},")
            ->line("Subscription **{$planName}** của **{$orgName}** sẽ hết hạn vào **{$endsAt}** ({$this->daysLeft} ngày nữa).")
            ->line('Vui lòng gia hạn để tiếp tục sử dụng đầy đủ tính năng.')
            ->action('Gia hạn ngay', route('subscription.portal.billing'))
            ->line('Cảm ơn bạn đã tin dùng dịch vụ của chúng tôi.');
    }
}
