<?php

namespace Modules\KpiGoal\Enums;

enum KpiGoalStatus: string
{
    case Draft     = 'draft';
    case Active    = 'active';
    case Completed = 'completed';
    case Cancelled = 'cancelled';

    public function label(): string
    {
        return match($this) {
            self::Draft     => 'Bản nháp',
            self::Active    => 'Đang theo dõi',
            self::Completed => 'Đã chốt',
            self::Cancelled => 'Đã huỷ',
        };
    }

    public function badgeClass(): string
    {
        return match($this) {
            self::Draft     => 'badge-ghost',
            self::Active    => 'badge-info',
            self::Completed => 'badge-success',
            self::Cancelled => 'badge-error',
        };
    }

    /** Statuses that count toward weight sum validation */
    public static function weightedStatuses(): array
    {
        return [self::Active->value, self::Completed->value];
    }
}
