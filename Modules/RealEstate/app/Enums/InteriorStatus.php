<?php

namespace Modules\RealEstate\Enums;

enum InteriorStatus: string
{
    case Full       = 'day_du';
    case Basic      = 'co_ban';
    case Unfinished = 'ban_giao_tho';

    public function label(): string
    {
        return match ($this) {
            self::Full       => 'Nội thất đầy đủ',
            self::Basic      => 'Nội thất cơ bản',
            self::Unfinished => 'Bàn giao thô',
        };
    }
}
