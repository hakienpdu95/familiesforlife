<?php

namespace Modules\PerformanceReview\Notifications;

use App\Notifications\Concerns\RespectsNotificationPreferences;
use App\Shared\Notifications\NotificationData;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Notification;
use Modules\PerformanceReview\Models\PerformanceReview;

class ReviewSubmittedNotification extends Notification implements ShouldQueue
{
    use Queueable, RespectsNotificationPreferences;

    public function __construct(private readonly PerformanceReview $review) {}

    protected function notificationType(): string { return 'review_submitted'; }

    public function toDatabase(object $notifiable): array
    {
        $employeeName = $this->review->employee->full_name
            ?? $this->review->employee->user?->name
            ?? '—';

        return NotificationData::make(
            type:     'review_submitted',
            title:    "Self-assessment đã được nộp",
            body:     "{$employeeName} đã nộp self-assessment kỳ {$this->review->period}. Vui lòng hoàn tất đánh giá.",
            url:      route('backend.performance-reviews.show', $this->review),
            icon:     'check',
            severity: 'info',
            meta:     [
                'review_id'   => $this->review->id,
                'employee_id' => $this->review->employee_id,
                'period'      => $this->review->period,
            ],
        );
    }
}
