<?php

namespace Modules\Leave\Notifications;

use App\Notifications\Concerns\RespectsNotificationPreferences;
use App\Shared\Notifications\NotificationData;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\BroadcastMessage;
use Illuminate\Notifications\Notification;
use Modules\Leave\Models\LeaveRequest;

class LeaveSubmittedNotification extends Notification implements ShouldQueue
{
    use Queueable, RespectsNotificationPreferences;

    public function __construct(private readonly LeaveRequest $leave) {}

    protected function notificationType(): string { return 'leave_submitted'; }

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
        $employee = $this->leave->employee;
        $name     = $employee->full_name ?? $employee->user?->name ?? '—';
        $type     = $this->leave->leave_type instanceof \BackedEnum
            ? $this->leave->leave_type->label()
            : (string) $this->leave->leave_type;
        $from = $this->leave->date_from?->format('d/m/Y') ?? '—';
        $to   = $this->leave->date_to?->format('d/m/Y')   ?? '—';

        return NotificationData::make(
            type:     'leave_submitted',
            title:    "Đơn xin nghỉ cần duyệt từ {$name}",
            body:     "{$name} xin nghỉ {$type} từ {$from} đến {$to}. Vui lòng xem xét và phê duyệt.",
            url:      route('backend.leave.requests.show', $this->leave),
            icon:     'user',
            severity: 'info',
            meta:     [
                'leave_id'   => $this->leave->id,
                'employee_id'=> $this->leave->employee_id,
                'date_from'  => $this->leave->date_from?->toDateString(),
                'date_to'    => $this->leave->date_to?->toDateString(),
            ],
        );
    }
}
