<?php

namespace Modules\Recruitment\Enums;

enum CandidateStatus: string
{
    case Active      = 'active';
    case Hired       = 'hired';
    case Blacklisted = 'blacklisted';
    case Inactive    = 'inactive';

    public function label(): string
    {
        return match ($this) {
            self::Active      => 'Đang hoạt động',
            self::Hired       => 'Đã tuyển',
            self::Blacklisted => 'Blacklist',
            self::Inactive    => 'Không hoạt động',
        };
    }
}
