<?php

namespace Modules\JobPosting\Enums;

enum ExperienceLevel: string
{
    case NoExperience = 'no_experience';
    case Entry        = 'entry';
    case Junior       = 'junior';
    case Mid          = 'mid';
    case Senior       = 'senior';
    case Lead         = 'lead';
    case Executive    = 'executive';

    public function label(): string
    {
        return match($this) {
            self::NoExperience => 'Không cần kinh nghiệm',
            self::Entry        => 'Dưới 1 năm',
            self::Junior       => '1–3 năm',
            self::Mid          => '3–5 năm',
            self::Senior       => '5–8 năm',
            self::Lead         => '8+ năm / Quản lý',
            self::Executive    => 'C-level / Giám đốc',
        };
    }
}
