<?php

namespace Modules\Marketplace\Enums;

enum ApplicantAvailability: string
{
    case Immediate    = 'immediate';
    case TwoWeeks     = '2_weeks';
    case OneMonth     = '1_month';
    case Negotiable   = 'negotiable';
    case NotAvailable = 'not_available';

    public function label(): string
    {
        return match ($this) {
            self::Immediate    => 'Ngay lập tức',
            self::TwoWeeks     => '2 tuần',
            self::OneMonth     => '1 tháng',
            self::Negotiable   => 'Thỏa thuận',
            self::NotAvailable => 'Chưa sẵn sàng',
        };
    }
}
