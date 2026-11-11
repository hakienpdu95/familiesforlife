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
            self::Full       => __('Nội thất đầy đủ'),
            self::Basic      => __('Nội thất cơ bản'),
            self::Unfinished => __('Bàn giao thô'),
        };
    }
}
