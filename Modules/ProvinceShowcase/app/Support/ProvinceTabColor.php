<?php

namespace Modules\ProvinceShowcase\Support;

class ProvinceTabColor
{
    public static function normalize(?string $hex): ?string
    {
        $hex = strtolower(trim((string) $hex));

        return preg_match('/^#[0-9a-f]{6}$/', $hex) ? $hex : null;
    }

    public static function textColor(string $hex): string
    {
        [$r, $g, $b] = array_map(
            fn (string $c) => ($v = hexdec($c) / 255) <= 0.03928 ? $v / 12.92 : (($v + 0.055) / 1.055) ** 2.4,
            str_split(ltrim($hex, '#'), 2),
        );

        return '#ffffff';
    }

    public static function default(): string
    {
        return self::normalize(config('provinceshowcase.default_theme_color')) ?? '#1d4ed8';
    }

    public static function resolve(?string $hex): string
    {
        return self::normalize($hex) ?? self::default();
    }

    public static function style(?string $hex): string
    {
        $hex = self::resolve($hex);

        return "background-color: {$hex}; color: ".self::textColor($hex);
    }
}
