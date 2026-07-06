<?php

namespace Modules\Assessment\Jobs;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Notification;
use Modules\Assessment\Notifications\InactiveMembersReportNotification;
use Modules\Organization\Enums\MemberStatus;
use Modules\Organization\Models\OrganizationMember;

/**
 * Chạy weekly (mỗi thứ 2 lúc 08:00).
 * Tìm thành viên không có hoạt động > 45 ngày, gửi báo cáo HR.
 */
class FlagInactiveMembersJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public function handle(): void
    {
        $threshold = now()->subDays(45);

        OrganizationMember::where('status', MemberStatus::Active->value)
            ->where(function ($q) use ($threshold) {
                $q->whereNull('last_active_at')
                  ->orWhere('last_active_at', '<', $threshold);
            })
            ->with(['user', 'organization'])
            ->chunkById(200, function ($members) {
                // 1 email per org, không spam từng thành viên
                $byOrg = $members->groupBy('organization_id');

                foreach ($byOrg as $orgMembers) {
                    $hrAdmins = $orgMembers->first()->organization->hrAdmins();
                    if ($hrAdmins && $hrAdmins->isNotEmpty()) {
                        Notification::send($hrAdmins, new InactiveMembersReportNotification($orgMembers));
                    }
                }
            });
    }
}
