<?php

namespace Modules\Marketplace\Enums;

enum PosterType: string
{
    case ORG         = 'org';
    case PENDING_ORG = 'pending_org';
    case INDIVIDUAL  = 'individual';

    public function label(): string
    {
        return match ($this) {
            self::ORG         => 'Tổ chức',
            self::PENDING_ORG => 'Tổ chức chờ duyệt',
            self::INDIVIDUAL  => 'Cá nhân',
        };
    }
}
