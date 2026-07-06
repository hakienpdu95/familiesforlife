<?php

namespace Modules\Marketplace\Enums;

enum ListingType: string
{
    case JOB      = 'job';
    case PROJECT  = 'project';
    case RESOURCE = 'resource';

    public function label(): string
    {
        return match ($this) {
            self::JOB      => 'Việc làm',
            self::PROJECT  => 'Dự án',
            self::RESOURCE => 'Freelancer',
        };
    }
}
