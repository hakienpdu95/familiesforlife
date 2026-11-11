<?php

namespace App\Shared\Traceability\Notifications;

use App\Notifications\Concerns\RespectsNotificationPreferences;
use App\Shared\Notifications\NotificationData;
use App\Shared\Traceability\Models\QrReport;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Notification;
use Illuminate\Support\Str;

class QrReportSubmittedNotification extends Notification implements ShouldQueue
{
    use Queueable, RespectsNotificationPreferences;

    public function __construct(
        private readonly QrReport $report,
        private readonly string $targetLabel,
    ) {}

    protected function notificationType(): string
    {
        return 'qr_report_submitted';
    }

    public function toDatabase(object $notifiable): array
    {
        return NotificationData::make(
            type: 'qr_report_submitted',
            title: __('Phản ánh :ref về mã QR :code — :target', ['ref' => $this->report->ref(), 'code' => $this->report->identity?->qr_code, 'target' => $this->targetLabel]),
            body: $this->report->reasonLabel().': '.Str::limit($this->report->content, 140),
            url: route('backend.qr-reports.index'),
            icon: 'warning',
            severity: $this->report->reason === 'suspected_fake' ? 'error' : 'warning',
        );
    }
}
