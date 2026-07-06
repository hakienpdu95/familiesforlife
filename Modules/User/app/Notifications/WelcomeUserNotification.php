<?php

namespace Modules\User\Notifications;

use App\Enums\RoleEnum;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class WelcomeUserNotification extends Notification implements ShouldQueue
{
    use Queueable;

    public function __construct(
        private readonly string  $temporaryPassword,
        private readonly string  $systemRole,
    ) {}

    public function via(object $notifiable): array
    {
        return ['mail'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        $roleLabel = RoleEnum::tryFrom($this->systemRole)?->label() ?? $this->systemRole;

        return (new MailMessage)
            ->subject('Tài khoản của bạn đã được tạo')
            ->greeting('Xin chào ' . $notifiable->name . '!')
            ->line('Quản trị viên đã tạo tài khoản cho bạn trong hệ thống.')
            ->line('**Thông tin đăng nhập:**')
            ->line('• Email: **' . $notifiable->email . '**')
            ->line('• Mật khẩu tạm: **' . $this->temporaryPassword . '**')
            ->line('• Vai trò: **' . $roleLabel . '**')
            ->action('Đăng nhập ngay', rtrim(config('app.url'), '/') . '/login')
            ->line('⚠️ Vui lòng đổi mật khẩu ngay sau khi đăng nhập lần đầu.')
            ->salutation('Trân trọng, Đội ngũ hỗ trợ');
    }
}
