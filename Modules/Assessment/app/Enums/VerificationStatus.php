<?php

namespace Modules\Assessment\Enums;

enum VerificationStatus: string
{
    case Pending  = 'pending';
    case Verified = 'verified';
    case Rejected = 'rejected';
    case Expired  = 'expired';

    public function label(): string
    {
        return match ($this) {
            self::Pending  => 'Chờ xác minh',
            self::Verified => 'Đã xác minh',
            self::Rejected => 'Bị từ chối',
            self::Expired  => 'Hết hạn',
        };
    }
}
