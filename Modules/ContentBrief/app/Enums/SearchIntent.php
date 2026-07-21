<?php

namespace Modules\ContentBrief\Enums;

enum SearchIntent: string
{
    case Informational = 'informational';
    case Transactional = 'transactional';
    case Navigational  = 'navigational';
    case Commercial    = 'commercial';

    public function label(): string
    {
        return match ($this) {
            self::Informational => 'Cung cấp thông tin',
            self::Transactional => 'Giao dịch/mua hàng',
            self::Navigational  => 'Điều hướng/tìm trang',
            self::Commercial    => 'So sánh/cân nhắc mua',
        };
    }
}
