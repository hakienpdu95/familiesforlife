<?php
namespace Modules\Customer\Enums;

enum CustomerActivityType: int
{
    case Call    = 1;
    case Email   = 2;
    case Meeting = 3;
    case Note    = 4;
    case Task    = 5;
    case Other   = 6;

    public function label(): string
    {
        return match($this) {
            self::Call    => 'Gọi điện',
            self::Email   => 'Email',
            self::Meeting => 'Họp',
            self::Note    => 'Ghi chú',
            self::Task    => 'Tác vụ',
            self::Other   => 'Khác',
        };
    }

    public function icon(): string
    {
        return match($this) {
            self::Call    => '📞',
            self::Email   => '✉️',
            self::Meeting => '🤝',
            self::Note    => '📝',
            self::Task    => '✅',
            self::Other   => '💬',
        };
    }
}
