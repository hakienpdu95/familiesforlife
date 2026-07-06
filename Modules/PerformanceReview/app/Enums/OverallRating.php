<?php

namespace Modules\PerformanceReview\Enums;

enum OverallRating: string
{
    case Excellent    = 'excellent';
    case Good         = 'good';
    case Average      = 'average';
    case BelowAverage = 'below_average';
    case Poor         = 'poor';

    public function label(): string
    {
        return match($this) {
            self::Excellent    => 'Xuất sắc',
            self::Good         => 'Tốt',
            self::Average      => 'Trung bình',
            self::BelowAverage => 'Dưới trung bình',
            self::Poor         => 'Kém',
        };
    }

    public function badgeClass(): string
    {
        return match($this) {
            self::Excellent    => 'badge-success',
            self::Good         => 'badge-info',
            self::Average      => 'badge-warning',
            self::BelowAverage => 'badge-error',
            self::Poor         => 'badge-error',
        };
    }
}
