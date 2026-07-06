<?php

namespace Modules\JobPosting\Enums;

enum EducationLevel: string
{
    case None       = 'none';
    case HighSchool = 'high_school';
    case Associate  = 'associate';
    case Bachelor   = 'bachelor';
    case Master     = 'master';
    case Phd        = 'phd';
    case Any        = 'any';

    public function label(): string
    {
        return match($this) {
            self::None       => 'Không yêu cầu',
            self::HighSchool => 'THPT',
            self::Associate  => 'Cao đẳng',
            self::Bachelor   => 'Đại học',
            self::Master     => 'Thạc sĩ',
            self::Phd        => 'Tiến sĩ',
            self::Any        => 'Không giới hạn',
        };
    }
}
