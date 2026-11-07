<?php

namespace Modules\Post\Features\ArticleAuthoring\Notifications;

use App\Notifications\Concerns\RespectsNotificationPreferences;
use App\Shared\Notifications\NotificationData;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;
use Modules\Post\Models\PostArticleTranslation;

class ArticleReviewNotification extends Notification implements ShouldQueue
{
    use Queueable, RespectsNotificationPreferences;

    public function __construct(
        private readonly PostArticleTranslation $translation,
        private readonly string $event,
        private readonly ?string $actorName = null,
        private readonly ?string $reason = null,
    ) {}

    protected function notificationType(): string
    {
        return 'post_article_review';
    }

    public function toDatabase(object $notifiable): array
    {
        return NotificationData::make(
            type: 'post_article_review',
            title: $this->title(),
            body: $this->body(),
            url: $this->url(),
            icon: $this->event === 'returned' ? 'warning' : 'task',
            severity: $this->event === 'returned' ? 'warning' : 'info',
        );
    }

    public function toMail(object $notifiable): MailMessage
    {
        return (new MailMessage)
            ->subject($this->title())
            ->line($this->body())
            ->action('Xem bài viết', $this->url());
    }

    private function title(): string
    {
        $label = "\"{$this->translation->title}\" ({$this->translation->locale})";

        return match ($this->event) {
            'submitted' => "Bài viết {$label} đang chờ bạn duyệt",
            'approved' => "Bài viết {$label} đã qua sơ duyệt, chờ duyệt xuất bản",
            'returned' => "Bài viết {$label} bị trả lại để chỉnh sửa",
        };
    }

    private function body(): string
    {
        $actor = $this->actorName ?? 'Hệ thống';

        return match ($this->event) {
            'submitted' => "{$actor} vừa gửi duyệt bài viết.",
            'approved' => "{$actor} đã duyệt sơ bộ, cần Tổng biên tập duyệt cuối và xuất bản.",
            'returned' => "{$actor} trả lại bài viết: {$this->reason}",
        };
    }

    private function url(): string
    {
        return route('backend.post.articles.edit', $this->translation->article);
    }
}
