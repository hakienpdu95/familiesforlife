<?php

namespace Modules\Post\Features\ArticleAuthoring\Notifications;

use App\Notifications\Concerns\RespectsNotificationPreferences;
use App\Shared\Notifications\NotificationData;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;
use Modules\Post\Models\PostArticleTranslation;

/** §7.6 — gửi khi TakeDownArticleTranslationAction gỡ khẩn cấp 1 translation. */
class ArticleTakenDownNotification extends Notification implements ShouldQueue
{
    use Queueable, RespectsNotificationPreferences;

    public function __construct(
        private readonly PostArticleTranslation $translation,
        private readonly string $reason,
    ) {}

    protected function notificationType(): string
    {
        return 'post_article_taken_down';
    }

    public function toDatabase(object $notifiable): array
    {
        return NotificationData::make(
            type: 'post_article_taken_down',
            title: __('Bài viết ":title" (:locale) đã bị gỡ khẩn cấp', ['title' => $this->translation->title, 'locale' => $this->translation->locale]),
            body: $this->reason,
            url: route('backend.post.articles.edit', $this->translation->article),
            icon: 'warning',
            severity: 'error',
        );
    }

    public function toMail(object $notifiable): MailMessage
    {
        return (new MailMessage)
            ->subject(__('Bài viết ":title" (:locale) đã bị gỡ khẩn cấp', ['title' => $this->translation->title, 'locale' => $this->translation->locale]))
            ->line($this->reason)
            ->action(__('Xem bài viết'), route('backend.post.articles.edit', $this->translation->article));
    }
}
