<?php

namespace Modules\PerformanceReview\Notifications;

use App\Notifications\Concerns\RespectsNotificationPreferences;
use App\Shared\Notifications\NotificationData;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Notification;

class ReviewPeriodStartedNotification extends Notification implements ShouldQueue
{
    use Queueable, RespectsNotificationPreferences;

    public function __construct(
        private readonly string $period,
        private readonly ?string $periodEnd = null,
    ) {}

    protected function notificationType(): string { return 'review_period_started'; }

    public function toDatabase(object $notifiable): array
    {
        $deadline = $this->periodEnd
            ? \Carbon\Carbon::parse($this->periodEnd)->format('d/m/Y')
            : '—';

        return NotificationData::make(
            type:     'review_period_started',
            title:    "Kỳ đánh giá {$this->period} đã bắt đầu",
            body:     "Kỳ đánh giá hiệu suất {$this->period} đã mở. Hạn nộp self-assessment: {$deadline}.",
            url:      route('backend.performance-reviews.index'),
            icon:     'check',
            severity: 'info',
            meta:     ['period' => $this->period, 'period_end' => $this->periodEnd],
        );
    }
}
