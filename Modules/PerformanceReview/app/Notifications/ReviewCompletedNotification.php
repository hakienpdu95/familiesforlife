<?php

namespace Modules\PerformanceReview\Notifications;

use App\Notifications\Concerns\RespectsNotificationPreferences;
use App\Shared\Notifications\NotificationData;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Notification;
use Modules\PerformanceReview\Models\PerformanceReview;

class ReviewCompletedNotification extends Notification implements ShouldQueue
{
    use Queueable, RespectsNotificationPreferences;

    public function __construct(private readonly PerformanceReview $review) {}

    protected function notificationType(): string { return 'review_completed'; }

    public function toDatabase(object $notifiable): array
    {
        $rating = $this->review->overall_rating instanceof \BackedEnum
            ? $this->review->overall_rating->label()
            : (string) ($this->review->overall_rating ?? '—');

        return NotificationData::make(
            type:     'review_completed',
            title:    "Đánh giá hiệu suất kỳ {$this->review->period} đã hoàn tất",
            body:     "Kết quả đánh giá của bạn kỳ {$this->review->period} đã được xác nhận. Xếp loại: {$rating}.",
            url:      route('backend.performance-reviews.show', $this->review),
            icon:     'success',
            severity: 'success',
            meta:     [
                'review_id'      => $this->review->id,
                'period'         => $this->review->period,
                'overall_rating' => $this->review->overall_rating?->value ?? null,
                'overall_score'  => $this->review->overall_score,
            ],
        );
    }
}
