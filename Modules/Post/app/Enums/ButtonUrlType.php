<?php

namespace Modules\Post\Enums;

enum ButtonUrlType: string
{
    case UseProductLink = 'use_product_link'; // resolve qua product_link_type + Modules\Product\Enums\ProductLinkType::urlColumn()
    case CustomUrl      = 'custom_url';
    case Phone          = 'phone';
    case Zalo           = 'zalo';
    case Email          = 'email';

    public function label(): string
    {
        return match ($this) {
            self::UseProductLink => 'Link sản phẩm',
            self::CustomUrl      => 'Link tuỳ ý',
            self::Phone          => 'Số điện thoại',
            self::Zalo           => 'Zalo',
            self::Email          => 'Email',
        };
    }
}
