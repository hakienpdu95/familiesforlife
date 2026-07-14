<?php

namespace Modules\Event\Features\EventModeration\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;
use Modules\Event\Enums\EventStatus;
use Modules\Event\Models\Event;

/**
 * Gửi cho `event_submissions.submitter_email` khi Approve/Reject (spec §11.2) — KHÔNG gửi khi
 * Publish (độc giả không có tài khoản để "thấy" thêm 1 thông báo khác, kết quả đã rõ ở bước
 * này). Recipient là email thô, không phải User — dùng on-demand notification
 * (`Notification::route('mail', $email)->notify(...)`), KHÔNG dùng RespectsNotificationPreferences
 * (trait đó đọc preference của User thật, không áp dụng cho độc giả ẩn danh).
 */
class EventApprovalResultNotification extends Notification implements ShouldQueue
{
    use Queueable;

    public function __construct(private readonly Event $event) {}

    public function via(object $notifiable): array
    {
        return ['mail'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        return $this->event->status === EventStatus::Rejected
            ? $this->rejectedMail()
            : $this->approvedMail();
    }

    private function approvedMail(): MailMessage
    {
        return (new MailMessage)
            ->subject("Sự kiện \"{$this->event->title}\" đã được duyệt")
            ->line("Sự kiện \"{$this->event->title}\" bạn nộp đã được duyệt.")
            ->line('Sự kiện sẽ hiển thị công khai trên cổng thông tin sau khi được xuất bản.')
            ->line('Cảm ơn bạn đã đóng góp cho cộng đồng!');
    }

    private function rejectedMail(): MailMessage
    {
        $message = (new MailMessage)
            ->subject("Sự kiện \"{$this->event->title}\" chưa được duyệt")
            ->line("Rất tiếc, sự kiện \"{$this->event->title}\" bạn nộp chưa được duyệt.");

        if ($this->event->rejected_reason) {
            $message->line("Lý do: {$this->event->rejected_reason}");
        }

        return $message->line('Bạn có thể nộp lại sự kiện với thông tin đầy đủ/chính xác hơn.');
    }
}
