<?php

namespace App\Support;

final class PhoneNumber
{
    public const PATTERN = '/^0\d{9,10}$/';

    public static function normalize(?string $value): ?string
    {
        $digits = preg_replace('/[\s.\-()]/', '', (string) $value);

        if ($digits === '') {
            return null;
        }

        return preg_replace('/^(\+?84)(?=\d{9,10}$)/', '0', $digits);
    }

    public static function rules(): array
    {
        return ['nullable', 'string', 'regex:'.self::PATTERN];
    }
}
