<?php

namespace Modules\Assessment\Jobs;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Notification;
use Modules\Assessment\Notifications\MemberAutoSuspendedNotification;
use Modules\Assessment\Services\OrgMembershipService;
use Modules\Organization\Enums\MemberStatus;
use Modules\Organization\Models\OrganizationMember;

/**
 * Chạy daily lúc 01:00.
 * Suspend các membership đã quá contract_end_date nhưng HR chưa offboard.
 */
class AutoSuspendExpiredMembershipsJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public function handle(OrgMembershipService $service): void
    {
        OrganizationMember::where('status', MemberStatus::Active->value)
            ->whereNotNull('contract_end_date')
            ->where('contract_end_date', '<', today())
            ->with(['user', 'organization'])
            ->chunkById(100, function ($members) use ($service) {
                foreach ($members as $member) {
                    $service->suspend($member);

                    // Gửi cảnh báo cho HR admins của org
                    $hrAdmins = $member->organization->hrAdmins();
                    if ($hrAdmins && $hrAdmins->isNotEmpty()) {
                        Notification::send($hrAdmins, new MemberAutoSuspendedNotification($member));
                    }
                }
            });
    }
}
