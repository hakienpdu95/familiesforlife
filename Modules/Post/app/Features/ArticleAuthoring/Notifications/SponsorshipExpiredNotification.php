<?php

namespace Modules\Post\Features\ArticleAuthoring\Notifications;

use App\Notifications\Concerns\RespectsNotificationPreferences;
use App\Shared\Notifications\NotificationData;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;
use Modules\Post\Models\PostArticle;

/**
 * Gửi khi ExpireSponsoredArticlesJob tự động tắt is_sponsored. Từ v3.0
 * (spec/Platform_RBAC_Phase2_Specification.md §3.3) người nhận là nhân sự nền tảng
 * (platform_content_head/platform_ops), không còn phải nhân sự doanh nghiệp tài trợ — nội
 * dung phải nêu rõ `sponsor_name` để họ biết cần liên hệ lại đúng doanh nghiệp nào qua kênh
 * sales/CRM riêng, ngoài phạm vi hệ thống này.
 */
class SponsorshipExpiredNotification extends Notification implements ShouldQueue
{
    use Queueable, RespectsNotificationPreferences;

    public function __construct(
        private readonly PostArticle $article,
    ) {}

    protected function notificationType(): string
    {
        return 'post_sponsorship_expired';
    }

    private function title(): string
    {
        $name = $this->article->mainTranslation()?->title ?? "Bài viết #{$this->article->id}";

        return "Đã hết hạn tài trợ: \"{$name}\"";
    }

    public function toDatabase(object $notifiable): array
    {
        return NotificationData::make(
            type:     'post_sponsorship_expired',
            title:    $this->title(),
            body:     "Tài trợ của \"{$this->article->sponsor_name}\" (campaign \"{$this->article->campaign_code}\") đã hết hạn — bài viết vẫn giữ nguyên trạng thái xuất bản, chỉ tắt nhãn tài trợ. Liên hệ lại doanh nghiệp qua kênh sales/CRM nếu cần gia hạn.",
            url:      route('backend.post.articles.edit', $this->article),
            icon:     'info',
            severity: 'warning',
        );
    }

    public function toMail(object $notifiable): MailMessage
    {
        return (new MailMessage)
            ->subject($this->title())
            ->line("Tài trợ của \"{$this->article->sponsor_name}\" (campaign \"{$this->article->campaign_code}\") đã hết hạn.")
            ->line('Bài viết vẫn giữ nguyên trạng thái xuất bản — chỉ tắt nhãn/disclosure tài trợ.')
            ->line('Hệ thống không còn liên kết tới tổ chức tài trợ — nếu cần gia hạn, liên hệ lại doanh nghiệp qua kênh sales/CRM riêng.')
            ->action('Xem bài viết', route('backend.post.articles.edit', $this->article));
    }
}
