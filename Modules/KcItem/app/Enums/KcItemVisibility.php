<?php

namespace Modules\KcItem\Enums;

enum KcItemVisibility: string
{
    case Public     = 'public';
    case Internal   = 'internal';
    case Restricted = 'restricted';
    case Private    = 'private';

    public function label(): string
    {
        return match ($this) {
            self::Public     => 'Công khai (trong org)',
            self::Internal   => 'Nội bộ',
            self::Restricted => 'Hạn chế',
            self::Private    => 'Riêng tư',
        };
    }

    public static function options(): array
    {
        return array_map(
            fn (self $case) => ['value' => $case->value, 'text' => $case->label()],
            self::cases()
        );
    }
}
