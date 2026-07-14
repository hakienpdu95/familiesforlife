<?php

namespace Modules\Event\Enums;

enum EventPriceType: string
{
    case Free   = 'free';
    case Single = 'single';
    case Range  = 'range';

    public function label(): string
    {
        return match ($this) {
            self::Free   => 'Miễn phí',
            self::Single => 'Giá cố định',
            self::Range  => 'Khoảng giá',
        };
    }
}
