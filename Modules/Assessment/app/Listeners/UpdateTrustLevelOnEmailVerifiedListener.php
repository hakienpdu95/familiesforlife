<?php

namespace Modules\Assessment\Listeners;

use Illuminate\Auth\Events\Verified;
use Modules\Assessment\Enums\VerificationMethod;
use Modules\Assessment\Enums\VerificationStatus;
use Modules\Assessment\Models\IdentityVerification;

class UpdateTrustLevelOnEmailVerifiedListener
{
    public function handle(Verified $event): void
    {
        $user = $event->user;

        // Cập nhật trust_level lên 1 nếu chưa đạt
        if ($user->trust_level < 1) {
            $user->update(['trust_level' => 1]);
        }

        // Tạo identity_verifications row nếu chưa có
        IdentityVerification::firstOrCreate(
            [
                'user_id' => $user->id,
                'method'  => VerificationMethod::Email->value,
                'status'  => VerificationStatus::Verified->value,
            ],
            [
                'verified_at' => now(),
                'expires_at'  => null,
            ]
        );
    }
}
