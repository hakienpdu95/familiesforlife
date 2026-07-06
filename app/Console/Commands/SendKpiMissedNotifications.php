<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;
use Modules\KpiGoal\Enums\KpiGoalStatus;
use Modules\KpiGoal\Models\KpiGoal;
use Modules\KpiGoal\Notifications\KpiMissedNotification;

class SendKpiMissedNotifications extends Command
{
    protected $signature   = 'notifications:kpi-missed';
    protected $description = 'Gửi thông báo kpi_missed cho goals kết thúc hôm qua mà chưa đạt target';

    public function handle(): int
    {
        $yesterday = now()->subDay()->toDateString();

        $goals = KpiGoal::with(['employee.user', 'employee.manager.user'])
            ->whereDate('cycle_end', $yesterday)
            ->where('status', KpiGoalStatus::Active->value)
            ->where('achievement_pct', '<', 100)
            ->get();

        $sent = 0;

        foreach ($goals as $goal) {
            $notification = new KpiMissedNotification($goal);
            $notified     = collect();

            $owner = $goal->employee?->user;
            if ($owner) {
                $owner->notify($notification);
                $notified->push($owner->id);
                $sent++;
            }

            $manager = $goal->employee?->manager?->user;
            if ($manager && !$notified->contains($manager->id)) {
                $manager->notify($notification);
                $sent++;
            }
        }

        $this->info("Đã gửi {$sent} thông báo kpi-missed cho cycle_end={$yesterday}.");
        Log::info("notifications:kpi-missed sent={$sent} date={$yesterday}");

        return self::SUCCESS;
    }
}
