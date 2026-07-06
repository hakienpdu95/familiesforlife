<?php

namespace Modules\JobPosting\Enums;

enum Visibility: string
{
    case Public   = 'public';
    case Unlisted = 'unlisted';
    case Internal = 'internal';

    public function label(): string
    {
        return match($this) {
            self::Public   => 'Công khai',
            self::Unlisted => 'Có link mới vào được',
            self::Internal => 'Nội bộ',
        };
    }
}
