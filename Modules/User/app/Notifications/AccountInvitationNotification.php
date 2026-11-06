<?php

namespace Modules\User\Notifications;

use App\Enums\RoleEnum;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class AccountInvitationNotification extends Notification implements ShouldQueue
{
    use Queueable;

    public function __construct(private readonly string $token) {}

    public function via(object $notifiable): array
    {
        return ['mail'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        $url = route('password.reset', [
            'token' => $this->token,
            'email' => $notifiable->getEmailForPasswordReset(),
        ]);

        $roleName = $notifiable->getRoleNames()->first();
        $roleLabel = RoleEnum::tryFrom((string) $roleName)?->label() ?? $roleName;
        $expire = config('auth.passwords.'.config('fortify.passwords').'.expire');

        return (new MailMessage)
            ->subject('Tài khoản của bạn đã được tạo — '.config('app.name'))
            ->greeting('Xin chào '.$notifiable->name.',')
            ->line('Quản trị viên đã tạo tài khoản cho bạn trên '.config('app.name').'.')
            ->line('• Email đăng nhập: **'.$notifiable->email.'**')
            ->when($roleLabel, fn (MailMessage $m) => $m->line('• Vai trò: **'.$roleLabel.'**'))
            ->line('Bấm nút bên dưới để tự đặt mật khẩu cho tài khoản.')
            ->action('Đặt mật khẩu', $url)
            ->line('Liên kết hết hạn sau '.$expire.' phút. Nếu đã hết hạn, hãy dùng chức năng "Quên mật khẩu" hoặc nhờ quản trị viên gửi lại.');
    }
}
