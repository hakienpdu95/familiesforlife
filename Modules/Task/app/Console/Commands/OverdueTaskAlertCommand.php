<?php

namespace Modules\Task\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class OverdueTaskAlertCommand extends Command
{
    protected $signature = 'task:overdue-alert';

    protected $description = 'Báo cáo các công việc quá hạn chưa hoàn thành (query 9.4).';

    public function handle(): int
    {
        $organizations = DB::table('organizations')
            ->whereNull('deleted_at')
            ->pluck('id');

        $totalOverdue = 0;

        foreach ($organizations as $orgId) {
            $overdue = DB::table('tasks as t')
                ->join('employees as e', 'e.id', '=', 't.employee_id')
                ->join('projects as p', 'p.id', '=', 't.project_id')
                ->where('t.due_date', '<', now()->toDateString())
                ->whereNotIn('t.status', ['done', 'cancelled'])
                ->where('t.is_archived', false)
                ->whereNull('t.deleted_at')
                ->whereNotNull('t.employee_id')
                ->where('t.organization_id', $orgId)
                ->select([
                    't.id', 't.title', 't.due_date', 't.priority',
                    'e.full_name as assignee_name', 'p.name as project_name',
                ])
                ->orderBy('t.due_date')
                ->orderByRaw("CASE t.priority WHEN 'critical' THEN 1 WHEN 'high' THEN 2 WHEN 'medium' THEN 3 ELSE 4 END")
                ->get();

            if ($overdue->isEmpty()) {
                continue;
            }

            $totalOverdue += $overdue->count();

            $this->warn("Tổ chức #{$orgId}: {$overdue->count()} công việc quá hạn");

            foreach ($overdue as $task) {
                $daysOverdue = now()->diffInDays($task->due_date, false);
                $this->line(sprintf(
                    '  [%s] [%s] %s — %s (hạn: %s, %d ngày trễ)',
                    strtoupper($task->priority),
                    $task->project_name,
                    $task->title,
                    $task->assignee_name,
                    $task->due_date,
                    abs((int) $daysOverdue)
                ));
            }
        }

        if ($totalOverdue === 0) {
            $this->info('Không có công việc quá hạn.');
        } else {
            $this->warn("Tổng cộng: {$totalOverdue} công việc quá hạn.");
        }

        return self::SUCCESS;
    }
}
