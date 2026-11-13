<?php

namespace Modules\Post\Enums;

enum ButtonTarget: string
{
    case SelfTab = '_self';
    case NewTab = '_blank';

    public function label(): string
    {
        return match ($this) {
            self::SelfTab => __('Tab hiện tại'),
            self::NewTab => __('Tab mới'),
        };
    }
}
