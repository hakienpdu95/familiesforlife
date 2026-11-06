<?php

namespace App\Services\Dashboard;

use App\Models\User;
use Illuminate\Support\Collection;
use Modules\ActivityLog\Models\ActivityLog;

class DashboardService
{
    public function getData(User $user): array
    {
        $primaryRole = $user->getRoleNames()->first();

        return [
            'action_feed'     => $this->actionFeed($user),
            'recent_activity' => $this->recentActivity(),
            'primary_role'    => $primaryRole,
        ];
    }

    // ── Action Feed ───────────────────────────────────────────────────────────

    private function actionFeed(User $user): array
    {
        return [];
    }

    // ── Recent Activity ───────────────────────────────────────────────────────

    private function recentActivity(): Collection
    {
        return ActivityLog::query()
            ->with('causer:id,name')
            ->orderByDesc('created_at')
            ->limit(8)
            ->get(['id', 'description', 'subject_type', 'event', 'causer_id', 'causer_type', 'created_at']);
    }
}
