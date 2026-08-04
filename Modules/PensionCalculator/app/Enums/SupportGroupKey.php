<?php

namespace Modules\PensionCalculator\Enums;

/**
 * spec/bhxh/PensionCalculator_Technical_Specification.md §6.1 — 4 nhóm hỗ trợ nhà nước theo
 * Điều 5 Nghị định 159/2025/NĐ-CP.
 */
enum SupportGroupKey: string
{
    case PoorHousehold = 'poor_household';
    case NearPoorHousehold = 'near_poor_household';
    case EthnicMinority = 'ethnic_minority';
    case Other = 'other';

    public function label(): string
    {
        return match ($this) {
            self::PoorHousehold => 'Hộ nghèo / xã đảo, đặc khu',
            self::NearPoorHousehold => 'Hộ cận nghèo',
            self::EthnicMinority => 'Dân tộc thiểu số',
            self::Other => 'Người tham gia khác',
        };
    }
}
