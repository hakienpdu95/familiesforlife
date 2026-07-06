<?php

namespace Modules\Leave\Enums;

enum LeaveType: string
{
    case Annual        = 'annual';
    case Sick          = 'sick';
    case Maternity     = 'maternity';
    case Paternity     = 'paternity';
    case Unpaid        = 'unpaid';
    case Compensatory  = 'compensatory';
    case Bereavement   = 'bereavement';
    case Other         = 'other';

    public function label(): string
    {
        return match($this) {
            self::Annual       => 'Nghỉ phép năm',
            self::Sick         => 'Nghỉ ốm',
            self::Maternity    => 'Nghỉ thai sản (nữ)',
            self::Paternity    => 'Nghỉ thai sản (nam)',
            self::Unpaid       => 'Nghỉ không lương',
            self::Compensatory => 'Nghỉ bù',
            self::Bereavement  => 'Nghỉ tang',
            self::Other        => 'Khác',
        };
    }

    /** Types that do not require balance check */
    public static function noBalanceCheck(): array
    {
        return [self::Sick->value, self::Unpaid->value];
    }
}
