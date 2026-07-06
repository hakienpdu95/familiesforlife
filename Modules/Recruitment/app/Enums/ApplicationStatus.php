<?php

namespace Modules\Recruitment\Enums;

enum ApplicationStatus: string
{
    case Active       = 'active';
    case Hired        = 'hired';
    case Rejected     = 'rejected';
    case Withdrawn    = 'withdrawn';
    case OnHold       = 'on_hold';
    case Disqualified = 'disqualified';

    public function label(): string
    {
        return match ($this) {
            self::Active       => 'Đang xử lý',
            self::Hired        => 'Đã tuyển',
            self::Rejected     => 'Từ chối',
            self::Withdrawn    => 'Rút đơn',
            self::OnHold       => 'Tạm dừng',
            self::Disqualified => 'Loại tự động',
        };
    }
}
