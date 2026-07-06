<?php

namespace Modules\Assessment\Notifications;

use App\Models\User;
use App\Shared\Tenancy\Models\Organization;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class OrgExitPassportReadyMail extends Notification implements ShouldQueue
{
    use Queueable;
    public function __construct(
        private readonly User         $user,
        private readonly Organization $org,
    ) {}

    public function via(object $notifiable): array
    {
        return ['mail'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        return (new MailMessage())
            ->subject('Competency Passport của bạn đã được lưu')
            ->greeting("Xin chào {$this->user->name},")
            ->line("Quá trình làm việc của bạn tại **{$this->org->name}** đã được lưu vào Competency Passport.")
            ->line('Bạn có thể đăng nhập bất cứ lúc nào để xem, tải PDF, hoặc chia sẻ hồ sơ với nhà tuyển dụng tiếp theo.')
            ->action('Xem Competency Passport', url('/passport'))
            ->line('Cảm ơn bạn đã tin dùng hệ thống.');
    }
}
