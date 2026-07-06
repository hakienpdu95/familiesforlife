<?php

namespace Modules\Branch\Enums;

enum BranchType: string
{
    case Headquarters   = 'headquarters';
    case RegionalOffice = 'regional_office';
    case Branch         = 'branch';
    case Store          = 'store';
    case Warehouse      = 'warehouse';

    public function label(): string
    {
        return match ($this) {
            self::Headquarters   => 'Trụ sở chính',
            self::RegionalOffice => 'Văn phòng vùng',
            self::Branch         => 'Chi nhánh',
            self::Store          => 'Cửa hàng',
            self::Warehouse      => 'Kho',
        };
    }

    public function badgeClass(): string
    {
        return match ($this) {
            self::Headquarters   => 'badge-primary',
            self::RegionalOffice => 'badge-secondary',
            self::Branch         => 'badge-info',
            self::Store          => 'badge-warning',
            self::Warehouse      => 'badge-ghost',
        };
    }
}
