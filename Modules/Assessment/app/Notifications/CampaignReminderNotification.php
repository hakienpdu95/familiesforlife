<?php

namespace Modules\Assessment\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;
use Modules\Assessment\Models\CampaignParticipation;
use Modules\Assessment\Models\OpenAssessmentCampaign;

class CampaignReminderNotification extends Notification implements ShouldQueue
{
    use Queueable;

    public function __construct(
        public readonly OpenAssessmentCampaign $campaign,
        public readonly CampaignParticipation  $participation,
    ) {}

    public function via(mixed $notifiable): array
    {
        return ['mail'];
    }

    public function toMail(mixed $notifiable): MailMessage
    {
        $daysLeft = (int) now()->diffInDays($this->campaign->open_until);

        return (new MailMessage)
            ->subject("Nhắc nhở: Còn {$daysLeft} ngày — {$this->campaign->title}")
            ->greeting("Xin chào {$notifiable->name},")
            ->line("Bạn đã tham gia nhưng chưa hoàn thành đánh giá **{$this->campaign->title}**.")
            ->line("Hạn chót: **{$this->campaign->open_until->format('d/m/Y H:i')}**")
            ->action('Tiếp tục đánh giá', url("/campaigns/{$this->campaign->uuid}/workspace"))
            ->line('Hoàn thành đánh giá để được tổ chức xem xét hồ sơ của bạn.');
    }
}
