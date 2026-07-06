<?php
namespace Modules\Customer\Enums;

enum CompanySize: int
{
    case Micro      = 1;
    case Small      = 2;
    case Medium     = 3;
    case Large      = 4;
    case Enterprise = 5;

    public function label(): string
    {
        return match($this) {
            self::Micro      => 'Siêu nhỏ (< 10)',
            self::Small      => 'Nhỏ (10–50)',
            self::Medium     => 'Vừa (50–250)',
            self::Large      => 'Lớn (250–1.000)',
            self::Enterprise => 'Tập đoàn (1.000+)',
        };
    }
}
