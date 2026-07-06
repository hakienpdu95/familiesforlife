<?php

namespace Modules\Sop\Enums;

enum SopType: string
{
    case Internal   = 'internal';
    case Regulatory = 'regulatory';
    case Training   = 'training';
    case Emergency  = 'emergency';

    public function label(): string
    {
        return match($this) {
            self::Internal   => 'Nội bộ',
            self::Regulatory => 'Tuân thủ / ISO',
            self::Training   => 'Đào tạo',
            self::Emergency  => 'Khẩn cấp',
        };
    }
}
