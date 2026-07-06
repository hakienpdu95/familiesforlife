<?php

namespace Modules\PerformanceReview\Enums;

enum PeriodType: string
{
    case Monthly    = 'monthly';
    case Quarterly  = 'quarterly';
    case SemiAnnual = 'semi_annual';
    case Annual     = 'annual';
    case Probation  = 'probation';
    case Custom     = 'custom';

    public function label(): string
    {
        return match($this) {
            self::Monthly    => 'Hàng tháng',
            self::Quarterly  => 'Hàng quý',
            self::SemiAnnual => 'Nửa năm',
            self::Annual     => 'Hàng năm',
            self::Probation  => 'Thử việc',
            self::Custom     => 'Tùy chỉnh',
        };
    }
}
