<?php

namespace App\Services\Dashboard;

use App\Enums\RoleEnum;
use App\Models\User;
use App\Shared\Tenancy\TenantContext;
use Illuminate\Support\Collection;
use Modules\ActivityLog\Models\ActivityLog;

class DashboardService
{
    public function getData(User $user): array
    {
        $orgId       = TenantContext::getOrganizationId();
        $primaryRole = $user->getRoleNames()->first() ?? RoleEnum::VIEWER->value;

        return [
            'action_feed'     => $this->actionFeed($user, $orgId),
            'recent_activity' => $this->recentActivity($orgId),
            'primary_role'    => $primaryRole,
        ];
    }

    // ── Action Feed ───────────────────────────────────────────────────────────

    private function actionFeed(User $user, ?int $orgId): array
    {
        return [];
    }

    // ── Recent Activity ───────────────────────────────────────────────────────

    private function recentActivity(?int $orgId): Collection
    {
        return ActivityLog::where(function ($q) use ($orgId) {
                $q->where('organization_id', $orgId)
                  ->orWhereNull('organization_id');
            })
            ->with('causer:id,name')
            ->orderByDesc('created_at')
            ->limit(8)
            ->get(['id', 'description', 'subject_type', 'event', 'causer_id', 'causer_type', 'created_at', 'organization_id']);
    }
}
