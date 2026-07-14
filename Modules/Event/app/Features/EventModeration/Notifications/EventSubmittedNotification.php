<?php

namespace Modules\Event\Features\EventModeration\Notifications;

use App\Shared\Notifications\NotificationData;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;
use Modules\Event\Models\Event;

/**
 * Gửi cho platform_content_editor/platform_content_head khi có sự kiện mới nộp qua form công
 * khai (spec §11.2) — KHÔNG gửi khi staff tự tạo thẳng trong dashboard (đã biết vì chính họ tạo).
 *
 * CHỈ dùng kênh 'mail' (không dùng RespectsNotificationPreferences — trait đó mặc định bật cả
 * database+broadcast). Phát hiện lỗi hạ tầng có sẵn, KHÔNG liên quan Event: bảng `notifications`
 * hiện có PK `id` kiểu bigint auto-increment (sinh qua quy ước chung của
 * render_migration_file.json — mọi bảng đều tự thêm id/uuid/order_column), nhưng
 * Illuminate\Notifications\DatabaseNotification (base class chuẩn của Laravel) LUÔN insert
 * `id` bằng Str::uuid() — gây lỗi "Incorrect integer value" trên MySQL với BẤT KỲ notification
 * nào gọi toDatabase()/toBroadcast() trong toàn bộ ứng dụng (không riêng module này). Giữ
 * toDatabase() bên dưới để sẵn sàng bật lại qua RespectsNotificationPreferences ngay khi lỗi hạ
 * tầng này được sửa (đổi cột `notifications.id` sang uuid/char(36), khớp đúng thiết kế
 * NotificationController::findForUser(string $uuid) đã viết sẵn).
 */
class EventSubmittedNotification extends Notification implements ShouldQueue
{
    use Queueable;

    public function __construct(private readonly Event $event) {}

    public function via(object $notifiable): array
    {
        return ['mail'];
    }

    public function toDatabase(object $notifiable): array
    {
        return NotificationData::make(
            type:     'event_submitted',
            title:    "Sự kiện mới chờ duyệt: \"{$this->event->title}\"",
            body:     "Độc giả vừa nộp sự kiện \"{$this->event->title}\" qua cổng thông tin, đang chờ sơ duyệt.",
            url:      route('backend.event.index', ['status' => 'submitted']),
            icon:     'bell',
            severity: 'info',
        );
    }

    public function toMail(object $notifiable): MailMessage
    {
        return (new MailMessage)
            ->subject("Sự kiện mới chờ duyệt: \"{$this->event->title}\"")
            ->line("Độc giả vừa nộp sự kiện \"{$this->event->title}\" qua cổng thông tin.")
            ->action('Xem hàng chờ duyệt', route('backend.event.index', ['status' => 'submitted']));
    }
}
