<?php

namespace App\Services\Dashboard;

use App\Enums\RoleEnum;
use App\Models\User;
use App\Shared\Tenancy\TenantContext;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;
use Modules\ActivityLog\Models\ActivityLog;
use Modules\Lead\Enums\LeadStatus;
use Modules\Lead\Models\Lead;

class DashboardService
{
    public function getData(User $user): array
    {
        $orgId       = TenantContext::getOrganizationId();
        $primaryRole = $user->getRoleNames()->first() ?? RoleEnum::VIEWER->value;

        return [
            'kpi_cards'       => $this->kpiCards($user, $orgId, $primaryRole),
            'action_feed'     => $this->actionFeed($user, $orgId),
            'recent_activity' => $this->recentActivity($orgId),
            'primary_role'    => $primaryRole,
        ];
    }

    // ── KPI Cards ─────────────────────────────────────────────────────────────

    private function kpiCards(User $user, ?int $orgId, string $role): array
    {
        $cards = [];

        // ── Role-specific cards ───────────────────────────────────────────
        if ($this->isFull($role)) {
            // CEO / Admin / Ops / AI_OP
            $cards[] = $this->cardLeadsActive($orgId);
        } elseif ($role === RoleEnum::SALES->value) {
            $cards[] = $this->cardMyLeads($user, $orgId);
            $cards[] = $this->cardLeadsConvertedThisMonth($orgId);
        } elseif ($role === RoleEnum::MARKETING->value) {
            $cards[] = $this->cardLeadsActive($orgId);
        }

        return $cards;
    }

    private function isFull(string $role): bool
    {
        return in_array($role, [
            RoleEnum::CEO->value,
            RoleEnum::ADMIN->value,
            RoleEnum::OPS->value,
            RoleEnum::AI_OP->value,
        ]);
    }

    // ── Card builders ─────────────────────────────────────────────────────────

    private function cardLeadsActive(?int $orgId): array
    {
        $count = Lead::where('organization_id', $orgId)
            ->where('status', LeadStatus::Active->value)
            ->count();

        return [
            'id'     => 'leads_active',
            'label'  => 'Lead đang theo dõi',
            'value'  => $count,
            'icon'   => 'leads',
            'color'  => 'info',
            'urgent' => false,
            'link'   => route('lead.index'),
            'hint'   => 'Trạng thái Active',
        ];
    }

    private function cardMyLeads(User $user, ?int $orgId): array
    {
        $count = Lead::where('organization_id', $orgId)
            ->where('assigned_to', $user->id)
            ->where('status', LeadStatus::Active->value)
            ->count();

        return [
            'id'     => 'my_leads',
            'label'  => 'Lead của tôi',
            'value'  => $count,
            'icon'   => 'leads',
            'color'  => 'info',
            'urgent' => false,
            'link'   => route('lead.index'),
            'hint'   => 'Lead được giao cho bạn',
        ];
    }

    private function cardLeadsConvertedThisMonth(?int $orgId): array
    {
        $count = Lead::where('organization_id', $orgId)
            ->where('status', LeadStatus::Converted->value)
            ->whereBetween('updated_at', [now()->startOfMonth(), now()])
            ->count();

        return [
            'id'     => 'leads_converted',
            'label'  => 'Lead chuyển đổi tháng này',
            'value'  => $count,
            'icon'   => 'leads_won',
            'color'  => 'success',
            'urgent' => false,
            'link'   => route('lead.index'),
            'hint'   => Carbon::now()->format('M Y'),
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
