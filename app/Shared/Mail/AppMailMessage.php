<?php

namespace App\Shared\Mail;

use Illuminate\Notifications\Messages\MailMessage;

/**
 * Factory helper tạo MailMessage với branding mặc định của hệ thống.
 *
 * Dùng thay cho `new MailMessage` ở mọi Notification::toMail() để đảm bảo:
 *  - Greeting chuẩn tiếng Việt
 *  - Salutation chuẩn
 *  - Subject prefix nhất quán (nếu cần)
 *
 * Ví dụ:
 *   return AppMailMessage::make()
 *       ->subject('Tiêu đề email')
 *       ->greeting("Xin chào {$user->name},")
 *       ->line('Nội dung...')
 *       ->action('Xem ngay', $url);
 *
 *   // Với subject tự động có prefix [AppName]:
 *   return AppMailMessage::prefixed('Subscription sắp hết hạn')
 *       ->greeting("Xin chào {$user->name},")
 *       ->line('...');
 */
class AppMailMessage
{
    /**
     * Tạo MailMessage mới với salutation mặc định.
     */
    public static function make(): MailMessage
    {
        return (new MailMessage)->salutation(
            'Trân trọng, ' . config('app.name')
        );
    }

    /**
     * Tạo MailMessage với subject được prefix bởi [AppName].
     * Dùng cho email quan trọng cần nổi bật trong hộp thư (billing, security).
     */
    public static function prefixed(string $subject): MailMessage
    {
        return static::make()->subject('[' . config('app.name') . '] ' . $subject);
    }

    /**
     * Tạo MailMessage cho thông báo hệ thống (auto-generated).
     * Tự động thêm ghi chú "Email được gửi tự động" ở cuối.
     */
    public static function system(): MailMessage
    {
        return static::make()->line(
            '_Email này được gửi tự động bởi hệ thống. Vui lòng không trả lời trực tiếp._'
        );
    }

    /**
     * Tạo MailMessage cho lời mời (invite).
     * Greeting mặc định là "Xin chào,".
     */
    public static function invite(string $recipientName = ''): MailMessage
    {
        $greeting = $recipientName
            ? "Xin chào {$recipientName},"
            : 'Xin chào,';

        return static::make()->greeting($greeting);
    }
}
