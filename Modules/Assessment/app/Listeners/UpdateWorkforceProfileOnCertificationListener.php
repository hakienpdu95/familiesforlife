<?php

namespace Modules\Assessment\Listeners;

use Modules\Assessment\Events\CertificationIssued;
use Modules\Assessment\Models\WorkforceProfileHistory;
use Modules\Assessment\Services\CareerLevelService;

class UpdateWorkforceProfileOnCertificationListener
{
    public function handle(CertificationIssued $event): void
    {
        $profile = $event->profile;
        $cert    = $event->certification;

        $levelOrder = ['FOUNDATION' => 1, 'PRACTITIONER' => 2, 'PROFESSIONAL' => 3, 'LEADER' => 4];
        $newLevel   = $cert->definition?->level_code;

        $currentOrder = $levelOrder[$profile->highest_cert_level] ?? 0;
        $newOrder     = $levelOrder[$newLevel] ?? 0;

        $updates = ['certifications_count' => $profile->certifications()->where('status', 'active')->count()];

        if ($newOrder > $currentOrder) {
            $updates['highest_cert_level']      = $newLevel;
            $updates['highest_cert_issued_at']  = $cert->issued_at;
            $updates['highest_cert_expires_at'] = $cert->expires_at;
        }

        $profile->update($updates);

        WorkforceProfileHistory::create([
            'workforce_profile_id' => $profile->id,
            'event_type'           => 'certification',
            'source_id'            => $cert->id,
            'source_type'          => $cert::class,
            'maturity_level_after' => $newLevel,
            'notes'                => 'Certification issued: ' . $cert->definition?->cert_code,
            'recorded_at'          => now(),
        ]);

        // Cập nhật trust score
        $profile->refresh();
        $profile->update(['workforce_trust_score' => $profile->recalculateTrustScore()]);

        // Cert vừa được cấp → kiểm tra xem có thể thăng cấp độ nghề nghiệp không
        app(CareerLevelService::class)->checkAndAdvance($profile->fresh());
    }
}
