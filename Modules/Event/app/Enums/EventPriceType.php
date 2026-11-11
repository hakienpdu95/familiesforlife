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
            self::Free   => __('Miễn phí'),
            self::Single => __('Giá cố định'),
            self::Range  => __('Khoảng giá'),
        };
    }
}
