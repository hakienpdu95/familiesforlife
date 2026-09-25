<?php

namespace Modules\ProvinceShowcase\Enums;

enum ProvincePlaceType: string
{
    case ThanhPho = 'thanh-pho';
    case Tinh = 'tinh';

    public function label(): string
    {
        return match ($this) {
            self::ThanhPho => 'Thành phố TW',
            self::Tinh => 'Tỉnh',
        };
    }

    public function badgeClass(): string
    {
        return match ($this) {
            self::ThanhPho => 'badge-primary',
            self::Tinh => 'badge-ghost',
        };
    }

    public static function options(): array
    {
        return array_map(fn (self $t) => ['value' => $t->value, 'text' => $t->label()], self::cases());
    }
}
