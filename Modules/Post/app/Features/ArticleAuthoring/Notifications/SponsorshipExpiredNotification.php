<?php

namespace Modules\Post\Features\ArticleAuthoring\Notifications;

use App\Notifications\Concerns\RespectsNotificationPreferences;
use App\Shared\Notifications\NotificationData;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;
use Modules\Post\Models\PostArticle;

/** spec/dac-ta-ky-thuat-bai-viet-tai-tro.md §8 — gửi khi ExpireSponsoredArticlesJob tự động tắt is_sponsored. */
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
            body:     "Campaign \"{$this->article->campaign_code}\" đã hết hạn — bài viết vẫn giữ nguyên trạng thái xuất bản, chỉ tắt nhãn tài trợ.",
            url:      route('backend.post.articles.edit', $this->article),
            icon:     'info',
            severity: 'warning',
        );
    }

    public function toMail(object $notifiable): MailMessage
    {
        return (new MailMessage)
            ->subject($this->title())
            ->line("Campaign \"{$this->article->campaign_code}\" đã hết hạn tài trợ.")
            ->line('Bài viết vẫn giữ nguyên trạng thái xuất bản — chỉ tắt nhãn/disclosure tài trợ.')
            ->action('Xem bài viết', route('backend.post.articles.edit', $this->article));
    }
}
