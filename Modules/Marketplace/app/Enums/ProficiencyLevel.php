<?php

namespace Modules\Marketplace\Enums;

enum ProficiencyLevel: string
{
    case Beginner     = 'beginner';
    case Intermediate = 'intermediate';
    case Advanced     = 'advanced';
    case Expert       = 'expert';

    public function label(): string
    {
        return match ($this) {
            self::Beginner     => 'Cơ bản',
            self::Intermediate => 'Trung cấp',
            self::Advanced     => 'Nâng cao',
            self::Expert       => 'Chuyên gia',
        };
    }
}
