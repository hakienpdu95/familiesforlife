<?php

namespace Modules\Subscription\Enums;

enum InvoiceStatus: int
{
    case Pending = 1;
    case Paid    = 2;
    case Void    = 3;

    public function label(): string
    {
        return match($this) {
            self::Pending => 'Chờ thanh toán',
            self::Paid    => 'Đã thanh toán',
            self::Void    => 'Đã hủy',
        };
    }
}
