<?php

namespace Modules\JobPosting\Enums;

enum WorkArrangement: string
{
    case Onsite   = 'onsite';
    case Remote   = 'remote';
    case Hybrid   = 'hybrid';
    case Flexible = 'flexible';

    public function label(): string
    {
        return match($this) {
            self::Onsite   => 'Tại văn phòng',
            self::Remote   => 'Remote',
            self::Hybrid   => 'Hybrid',
            self::Flexible => 'Linh hoạt',
        };
    }
}
