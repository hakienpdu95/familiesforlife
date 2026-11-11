<?php

namespace Modules\Product\Enums;

enum ProductType: string
{
    case Physical = 'physical';
    case Service  = 'service';

    public function label(): string
    {
        return match ($this) {
            self::Physical => __('Sản phẩm vật lý'),
            self::Service  => __('Dịch vụ'),
        };
    }
}
