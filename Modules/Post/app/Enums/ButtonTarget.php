<?php

namespace Modules\Post\Enums;

enum ButtonTarget: string
{
    case SelfTab = '_self';
    case NewTab  = '_blank';

    public function label(): string
    {
        return match ($this) {
            self::SelfTab => 'Tab hiện tại',
            self::NewTab  => 'Tab mới',
        };
    }
}
