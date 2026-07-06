<?php

namespace Modules\Assessment\Enums;

enum ParticipationStatus: string
{
    case InProgress = 'in_progress';
    case Completed  = 'completed';
    case Abandoned  = 'abandoned';
    case Declined   = 'declined';

    public function label(): string
    {
        return match ($this) {
            self::InProgress => 'Đang tham gia',
            self::Completed  => 'Hoàn thành',
            self::Abandoned  => 'Đã huỷ',
            self::Declined   => 'Từ chối lời mời',
        };
    }

    public function badgeClass(): string
    {
        return match ($this) {
            self::InProgress => 'badge-info',
            self::Completed  => 'badge-success',
            self::Abandoned  => 'badge-ghost',
            self::Declined   => 'badge-warning',
        };
    }
}
