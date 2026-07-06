<?php

namespace Modules\Marketplace\Enums;

enum WorkType: string
{
    case ONSITE   = 'onsite';
    case REMOTE   = 'remote';
    case HYBRID   = 'hybrid';
    case FLEXIBLE = 'flexible';

    public function label(): string
    {
        return match ($this) {
            self::ONSITE   => 'Tại văn phòng',
            self::REMOTE   => 'Remote',
            self::HYBRID   => 'Hybrid',
            self::FLEXIBLE => 'Linh hoạt',
        };
    }
}
