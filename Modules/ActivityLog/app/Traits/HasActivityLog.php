<?php

namespace Modules\ActivityLog\Traits;

use Illuminate\Support\Collection;
use Modules\ActivityLog\Models\ActivityLog;

trait HasActivityLog
{
    /**
     * Lấy các log gần nhất của model này (30 ngày gần nhất).
     * Dùng FQCN (get_class) nhất quán với WriteActivityLogAction — không dùng class_basename.
     */
    public function recentActivityLogs(int $limit = 15): Collection
    {
        return ActivityLog::where('subject_type', get_class($this))
            ->where('subject_id', $this->getKey())
            ->where('created_at', '>=', now()->subDays(30))
            ->orderByDesc('created_at')
            ->limit($limit)
            ->get(['id', 'module', 'action', 'level', 'description', 'actor_name', 'actor_ip', 'created_at']);
    }
}
