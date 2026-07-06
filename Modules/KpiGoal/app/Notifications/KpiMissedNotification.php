<?php

namespace Modules\KpiGoal\Notifications;

use App\Notifications\Concerns\RespectsNotificationPreferences;
use App\Shared\Notifications\NotificationData;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Notification;
use Modules\KpiGoal\Models\KpiGoal;

class KpiMissedNotification extends Notification implements ShouldQueue
{
    use Queueable, RespectsNotificationPreferences;

    public function __construct(private readonly KpiGoal $goal) {}

    protected function notificationType(): string { return 'kpi_missed'; }

    public function toDatabase(object $notifiable): array
    {
        $pct = round((float) $this->goal->achievement_pct, 1);

        return NotificationData::make(
            type:     'kpi_missed',
            title:    "KPI chưa đạt: {$this->goal->title}",
            body:     "KPI \"{$this->goal->title}\" kỳ {$this->goal->cycle_label} kết thúc với {$pct}% — chưa đạt mục tiêu.",
            url:      route('backend.kpi.goals.show', $this->goal),
            icon:     'warning',
            severity: 'error',
            meta:     [
                'goal_id'         => $this->goal->id,
                'cycle_label'     => $this->goal->cycle_label,
                'achievement_pct' => $this->goal->achievement_pct,
                'target_value'    => $this->goal->target_value,
                'current_value'   => $this->goal->current_value,
            ],
        );
    }
}
