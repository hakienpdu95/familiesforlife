<?php

namespace Modules\Marketplace\Enums;

enum ExperienceLevel: string
{
    case ENTRY  = 'entry';
    case JUNIOR = 'junior';
    case MID    = 'mid';
    case SENIOR = 'senior';
    case LEAD   = 'lead';
    case ANY    = 'any';

    public function label(): string
    {
        return match ($this) {
            self::ENTRY  => 'Mới ra trường',
            self::JUNIOR => 'Junior (1-2 năm)',
            self::MID    => 'Mid (2-5 năm)',
            self::SENIOR => 'Senior (5+ năm)',
            self::LEAD   => 'Lead / Manager',
            self::ANY    => 'Không yêu cầu',
        };
    }
}
