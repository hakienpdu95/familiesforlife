<?php

namespace Modules\JobPosting\Notifications;

use App\Notifications\Concerns\RespectsNotificationPreferences;
use App\Shared\Notifications\NotificationData;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;
use Modules\JobPosting\Models\JpJobPost;

class JpJobPostExpiryWarningNotification extends Notification implements ShouldQueue
{
    use Queueable, RespectsNotificationPreferences;

    public function __construct(
        private readonly JpJobPost $post,
        private readonly int       $daysLeft,
    ) {}

    protected function notificationType(): string { return 'jp_expiry_warning'; }

    public function toDatabase(object $notifiable): array
    {
        return NotificationData::make(
            type:     'jp_expiry_warning',
            title:    "Tin tuyển dụng [{$this->post->code}] sắp hết hạn ({$this->daysLeft} ngày)",
            body:     "Tin tuyển dụng \"{$this->post->title}\" sẽ hết hạn vào {$this->post->expire_at?->format('d/m/Y')}. Vui lòng gia hạn hoặc đóng tin.",
            url:      route('backend.job-posts.show', $this->post->id),
            icon:     'warning',
            severity: 'warning',
            meta:     [
                'job_post_id'   => $this->post->id,
                'job_post_uuid' => $this->post->uuid,
                'expire_at'     => $this->post->expire_at?->toDateString(),
                'days_left'     => $this->daysLeft,
            ],
        );
    }

    public function toMail(object $notifiable): MailMessage
    {
        $url = route('backend.job-posts.show', $this->post->id);

        return (new MailMessage)
            ->subject("Tin tuyển dụng sắp hết hạn: [{$this->post->code}] {$this->post->title}")
            ->greeting("Xin chào {$notifiable->name},")
            ->line("Tin tuyển dụng **[{$this->post->code}] {$this->post->title}** sẽ hết hạn vào ngày **{$this->post->expire_at?->format('d/m/Y')}** ({$this->daysLeft} ngày nữa).")
            ->line('Vui lòng xem xét gia hạn hoặc đóng tin nếu không còn cần tuyển.')
            ->action('Xem tin tuyển dụng', $url);
    }
}
