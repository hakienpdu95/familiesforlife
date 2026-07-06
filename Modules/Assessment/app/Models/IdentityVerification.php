<?php

namespace Modules\Assessment\Models;

use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Modules\Assessment\Enums\VerificationMethod;
use Modules\Assessment\Enums\VerificationStatus;

class IdentityVerification extends Model
{
    protected $table = 'identity_verifications';

    protected $fillable = [
        'user_id',
        'method',
        'status',
        'verified_at',
        'expires_at',
        'rejection_reason',
        'email_candidate',
    ];

    protected function casts(): array
    {
        return [
            'method'          => VerificationMethod::class,
            'status'          => VerificationStatus::class,
            'verified_at'     => 'datetime',
            'expires_at'      => 'datetime',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function isActive(): bool
    {
        if ($this->status !== VerificationStatus::Verified) {
            return false;
        }

        return $this->expires_at === null || $this->expires_at->isFuture();
    }
}
