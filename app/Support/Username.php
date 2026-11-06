<?php

namespace App\Support;

use App\Models\User;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;

final class Username
{
    public const PATTERN = '/^[a-z0-9](?:[a-z0-9._-]*[a-z0-9])?$/';

    public const MIN = 3;

    public const MAX = 30;

    public static function rules(?int $ignoreId = null): array
    {
        return [
            'required', 'string', 'min:'.self::MIN, 'max:'.self::MAX, 'regex:'.self::PATTERN,
            'not_regex:/[._-]{2}/',
            Rule::unique('users', 'username')->ignore($ignoreId),
        ];
    }

    public static function normalize(?string $value): string
    {
        return Str::lower(trim((string) $value));
    }

    public static function suggest(string $seed, ?int $ignoreId = null): string
    {
        $base = Str::of(Str::before($seed, '@'))
            ->ascii()
            ->lower()
            ->replaceMatches('/[^a-z0-9._-]+/', '.')
            ->replaceMatches('/[._-]{2,}/', '.')
            ->trim('._-')
            ->limit(self::MAX - 4, '')
            ->trim('._-')
            ->value();

        $base = str_pad($base, self::MIN, '0');
        $candidate = $base;

        for ($i = 2; self::taken($candidate, $ignoreId); $i++) {
            $candidate = $base.$i;
        }

        return $candidate;
    }

    private static function taken(string $username, ?int $ignoreId): bool
    {
        return User::query()
            ->where('username', $username)
            ->when($ignoreId, fn ($q) => $q->whereKeyNot($ignoreId))
            ->exists();
    }
}
