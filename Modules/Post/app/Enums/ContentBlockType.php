<?php

namespace Modules\Post\Enums;

enum ContentBlockType: string
{
    case Text    = 'text';
    case Product = 'product';

    public function label(): string
    {
        return match ($this) {
            self::Text    => 'Đoạn văn bản',
            self::Product => 'Khối sản phẩm',
        };
    }
}
