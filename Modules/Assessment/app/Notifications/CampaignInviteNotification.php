<?php

namespace Modules\Assessment\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;
use Modules\Assessment\Models\CampaignParticipation;
use Modules\Assessment\Models\OpenAssessmentCampaign;

class CampaignInviteNotification extends Notification implements ShouldQueue
{
    use Queueable;
    public function __construct(
        private readonly OpenAssessmentCampaign $campaign,
        private readonly CampaignParticipation  $participation,
    ) {}

    public function via(object $notifiable): array
    {
        return ['mail'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        return (new MailMessage)
            ->subject("Lời mời từ {$this->campaign->organization?->name} — {$this->campaign->title}")
            ->greeting("Xin chào {$notifiable->name},")
            ->line("{$this->campaign->organization?->name} đã xem kết quả của bạn trong chiến dịch đánh giá **{$this->campaign->title}** và muốn mời bạn tham gia phỏng vấn.")
            ->line("Kết quả của bạn: **{$this->participation->result_tdwcf_score}** điểm TDWCF")
            ->action('Xem lời mời', url("/campaigns/{$this->campaign->uuid}"))
            ->line('Bạn có thể chấp nhận hoặc từ chối lời mời này trong mục Campaigns của mình.')
            ->salutation('Chúc bạn thành công!');
    }
}
