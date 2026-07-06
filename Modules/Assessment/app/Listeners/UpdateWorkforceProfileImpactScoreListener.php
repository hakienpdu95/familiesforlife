<?php

namespace Modules\Assessment\Listeners;

use Modules\Assessment\Actions\CalculateAiiAction;
use Modules\Assessment\Events\ImpactSnapshotRecorded;
use Modules\Assessment\Models\WorkforceProfile;
use Modules\Assessment\Models\WorkforceProfileHistory;

class UpdateWorkforceProfileImpactScoreListener
{
    public function __construct(
        private readonly CalculateAiiAction $calculateAii,
    ) {}

    public function handle(ImpactSnapshotRecorded $event): void
    {
        $snapshot = $event->snapshot;

        if (! $snapshot->employee_id) {
            return;
        }

        $profile = WorkforceProfile::withoutTenant()
            ->whereHas('employee', fn($q) => $q->withoutGlobalScopes()->where('id', $snapshot->employee_id))
            ->orWhere(function ($q) use ($snapshot) {
                // Fallback: match via employee model directly
                $q->where('employee_id', $snapshot->employee_id);
            })
            ->first();

        if (! $profile) {
            return;
        }

        $aii = $this->calculateAii->handle(
            $snapshot->employee_id,
            $snapshot->period_start,
            $snapshot->period_end,
        );

        $profile->update(['impact_score' => $aii]);

        WorkforceProfileHistory::create([
            'workforce_profile_id' => $profile->id,
            'event_type'           => 'impact',
            'source_id'            => $snapshot->id,
            'source_type'          => $snapshot::class,
            'notes'                => "AII recalculated: {$aii} (triggered by {$snapshot->impact_category}/{$snapshot->impact_type})",
            'recorded_at'          => now(),
        ]);

        // Cập nhật trust score sau khi impact_score thay đổi
        $profile->refresh();
        $profile->update(['workforce_trust_score' => $profile->recalculateTrustScore()]);
    }
}
