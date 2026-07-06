<?php

namespace Modules\Employee\Notifications;

use App\Notifications\Concerns\RespectsNotificationPreferences;
use App\Shared\Notifications\NotificationData;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Notification;
use Modules\Employee\Models\Employee;

class EmployeeOnboardedNotification extends Notification implements ShouldQueue
{
    use Queueable, RespectsNotificationPreferences;

    public function __construct(private readonly Employee $employee) {}

    protected function notificationType(): string { return 'employee_onboarded'; }

    public function toDatabase(object $notifiable): array
    {
        $name = $this->employee->full_name ?? '—';

        return NotificationData::make(
            type:     'employee_onboarded',
            title:    "Nhân viên mới: {$name}",
            body:     "Nhân viên {$name} vừa được thêm vào hệ thống và đã sẵn sàng làm việc.",
            url:      route('backend.employees.show', $this->employee),
            icon:     'user',
            severity: 'info',
            meta:     [
                'employee_id'   => $this->employee->id,
                'employee_code' => $this->employee->employee_code,
            ],
        );
    }
}
