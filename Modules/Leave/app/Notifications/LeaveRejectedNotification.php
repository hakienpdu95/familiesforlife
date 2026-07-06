<?php

namespace Modules\Leave\Notifications;

use App\Notifications\Concerns\RespectsNotificationPreferences;
use App\Shared\Notifications\NotificationData;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\BroadcastMessage;
use Illuminate\Notifications\Notification;
use Modules\Leave\Models\LeaveRequest;

class LeaveRejectedNotification extends Notification implements ShouldQueue
{
    use Queueable, RespectsNotificationPreferences;

    public function __construct(
        private readonly LeaveRequest $leave,
        private readonly ?string $reason = null,
    ) {}

    protected function notificationType(): string { return 'leave_rejected'; }

    public function toBroadcast(object $notifiable): BroadcastMessage
    {
        $data = $this->toDatabase($notifiable);
        $data['notification_type'] = $data['type'];
        unset($data['type']);
        return new BroadcastMessage($data);
    }

    public function toWebPush(object $notifiable): array
    {
        return $this->toDatabase($notifiable);
    }

    public function toDatabase(object $notifiable): array
    {
        $type = $this->leave->leave_type instanceof \BackedEnum
            ? $this->leave->leave_type->label()
            : (string) $this->leave->leave_type;
        $from   = $this->leave->date_from?->format('d/m/Y') ?? '—';
        $to     = $this->leave->date_to?->format('d/m/Y')   ?? '—';
        $reason = $this->reason ?? $this->leave->rejected_reason ?? '';
        $body   = "Đơn xin nghỉ {$type} từ {$from} đến {$to} đã bị từ chối."
                . ($reason ? " Lý do: {$reason}." : '');

        return NotificationData::make(
            type:     'leave_rejected',
            title:    'Đơn xin nghỉ bị từ chối',
            body:     $body,
            url:      route('backend.leave.requests.show', $this->leave),
            icon:     'warning',
            severity: 'warning',
            meta:     [
                'leave_id'  => $this->leave->id,
                'date_from' => $this->leave->date_from?->toDateString(),
                'date_to'   => $this->leave->date_to?->toDateString(),
                'reason'    => $reason,
            ],
        );
    }
}
