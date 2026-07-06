<?php

namespace Modules\Recruitment\Enums;

enum InterviewStatus: string
{
    case Scheduled = 'scheduled';
    case Confirmed = 'confirmed';
    case Completed = 'completed';
    case Cancelled = 'cancelled';
    case NoShow    = 'no_show';

    public function label(): string
    {
        return match($this) {
            self::Scheduled => 'Đã lên lịch',
            self::Confirmed => 'Đã xác nhận',
            self::Completed => 'Hoàn thành',
            self::Cancelled => 'Đã hủy',
            self::NoShow    => 'Vắng mặt',
        };
    }

    public function badgeClass(): string
    {
        return match($this) {
            self::Scheduled => 'badge-info',
            self::Confirmed => 'badge-primary',
            self::Completed => 'badge-success',
            self::Cancelled => 'badge-ghost',
            self::NoShow    => 'badge-error',
        };
    }
}
