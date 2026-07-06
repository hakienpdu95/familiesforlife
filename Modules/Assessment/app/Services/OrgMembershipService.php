<?php

namespace Modules\Assessment\Services;

use App\Enums\AccountType;
use App\Models\User;
use App\Shared\Tenancy\Models\Organization;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Mail;
use Modules\Assessment\Jobs\SnapshotPassportEntryJob;
use Modules\Assessment\Notifications\OrgExitPassportReadyMail;
use Modules\Organization\Enums\MemberStatus;
use Modules\Organization\Models\MemberPostExitAudit;
use Modules\Organization\Models\OrganizationMember;

class OrgMembershipService
{
    /**
     * Offboard nhân viên đúng hạn.
     * Snapshot Passport tại thời điểm hiện tại.
     */
    public function deactivate(User $user, Organization $org, array $options = []): void
    {
        DB::transaction(function () use ($user, $org, $options) {

            // 1. Đóng membership
            OrganizationMember::where('user_id', $user->id)
                ->where('organization_id', $org->id)
                ->where('status', MemberStatus::Active->value)
                ->update([
                    'status'              => MemberStatus::Inactive->value,
                    'left_at'             => now(),
                    'effective_left_at'   => now(),
                    'offboarded_at'       => now(),
                    'late_offboard_gap_days' => 0,
                    'exit_reason'         => $options['reason'] ?? 'resigned',
                    'exit_initiated_by'   => $options['initiated_by'] ?? 'hr',
                    'job_title_at_exit'   => $user->employee?->jobTitle?->name,
                    'department_at_exit'  => $user->employee?->department?->name,
                    'role_at_exit'        => $user->getRoleNames()->first(),
                ]);

            // 2. Thu hồi roles Spatie
            $user->syncRoles([]);

            // 3. Cập nhật users
            $user->update([
                'account_type'   => AccountType::Free->value,
                'current_org_id' => null,
            ]);

            // 4. Snapshot Passport entry (Phase 1)
            SnapshotPassportEntryJob::dispatch($user->id, $org->id)
                ->onQueue('passport');

            // 5. Thông báo
            Mail::to($user->email)->queue(new OrgExitPassportReadyMail($user, $org));
        });
    }

    /**
     * Offboard muộn — HR nhập ngày thực tế nhân viên đã nghỉ.
     * Snapshot Passport với cutoff tại effective_left_at.
     */
    public function deactivateLate(
        OrganizationMember $member,
        Carbon             $effectiveLeftAt,
        string             $exitReason = 'resigned'
    ): void {
        $offboardedAt = now();
        $gapDays      = (int) $effectiveLeftAt->diffInDays($offboardedAt);

        DB::transaction(function () use ($member, $effectiveLeftAt, $offboardedAt, $gapDays, $exitReason) {

            // 1. Đếm activity trong gap để ghi vào audit
            $loginCount   = $this->countLoginsInGap($member->user_id, $effectiveLeftAt, $offboardedAt);
            $sandboxCount = $this->countSandboxSessionsInGap(
                $member->organization_id, $member->user_id,
                $effectiveLeftAt, $offboardedAt
            );
            $lastLogin = $this->getLastLoginInGap($member->user_id, $effectiveLeftAt, $offboardedAt);

            // 2. Update membership với ngày thực tế
            $member->update([
                'status'                  => MemberStatus::Inactive->value,
                'left_at'                 => $effectiveLeftAt,
                'effective_left_at'       => $effectiveLeftAt,
                'offboarded_at'           => $offboardedAt,
                'late_offboard_gap_days'  => $gapDays,
                'exit_reason'             => $exitReason,
                'exit_initiated_by'       => 'hr',
                'job_title_at_exit'       => $member->user->employee?->jobTitle?->name,
                'department_at_exit'      => $member->user->employee?->department?->name,
                'role_at_exit'            => $member->user->getRoleNames()->first(),
            ]);

            // 3. Tạo audit record
            MemberPostExitAudit::create([
                'organization_id'         => $member->organization_id,
                'user_id'                 => $member->user_id,
                'org_membership_id'       => $member->id,
                'effective_left_at'       => $effectiveLeftAt,
                'offboarded_at'           => $offboardedAt,
                'gap_days'                => $gapDays,
                'login_count_in_gap'      => $loginCount,
                'sandbox_sessions_in_gap' => $sandboxCount,
                'last_login_in_gap'       => $lastLogin,
                'created_at'              => now(),
            ]);

            // 4. Snapshot Passport CUT OFF tại effectiveLeftAt (Phase 1)
            SnapshotPassportEntryJob::dispatch(
                $member->user_id,
                $member->organization_id,
                $effectiveLeftAt
            )->onQueue('passport');

            // 5. Revoke access ngay
            $member->user->syncRoles([]);
            $member->user->update([
                'account_type'   => AccountType::Free->value,
                'current_org_id' => null,
            ]);
        });
    }

    /**
     * Suspend membership khi hết contract_end_date (dùng bởi AutoSuspendJob).
     */
    public function suspend(OrganizationMember $member): void
    {
        DB::transaction(function () use ($member) {
            $member->update([
                'status'            => MemberStatus::Suspended->value,
                'auto_suspended_at' => now(),
            ]);

            // Revoke Spatie roles
            $member->user->syncRoles([]);
        });
    }

    // ── Private helpers ──────────────────────────────────────────────

    private function countLoginsInGap(int $userId, Carbon $from, Carbon $to): int
    {
        return (int) DB::table('sessions')
            ->where('user_id', $userId)
            ->whereBetween('created_at', [$from, $to])
            ->count();
    }

    private function countSandboxSessionsInGap(
        int    $orgId,
        int    $userId,
        Carbon $from,
        Carbon $to
    ): int {
        if (!DB::getSchemaBuilder()->hasTable('sandbox_sessions')) {
            return 0;
        }

        $profileId = DB::table('workforce_profiles')
            ->where('organization_id', $orgId)
            ->where('user_id', $userId)
            ->value('id');

        $query = DB::table('sandbox_sessions')
            ->where('organization_id', $orgId)
            ->whereBetween('created_at', [$from, $to]);

        if ($profileId) {
            $query->where('workforce_profile_id', $profileId);
        } else {
            $query->where('user_id', $userId);
        }

        return (int) $query->count();
    }

    private function getLastLoginInGap(int $userId, Carbon $from, Carbon $to): ?Carbon
    {
        $ts = DB::table('sessions')
            ->where('user_id', $userId)
            ->whereBetween('created_at', [$from, $to])
            ->max('created_at');

        return $ts ? Carbon::parse($ts) : null;
    }
}
