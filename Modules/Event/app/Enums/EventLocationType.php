<?php

namespace Modules\Event\Enums;

enum EventLocationType: string
{
    case Physical = 'physical';
    case Online   = 'online';

    public function label(): string
    {
        return match ($this) {
            self::Physical => 'Trực tiếp',
            self::Online   => 'Trực tuyến',
        };
    }
}
