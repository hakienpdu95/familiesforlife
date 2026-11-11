<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\QueryException;
use Illuminate\Support\Facades\Cache;

class SystemSetting extends Model
{
    public const CACHE_KEY = 'system_settings.current.v2';

    public const LOCALES = [
        'vi' => 'Tiếng Việt',
        'en' => 'English',
    ];

    public const DATE_FORMATS = ['d/m/Y', 'm/d/Y', 'Y-m-d', 'd.m.Y'];

    public const TIME_FORMATS = ['H:i', 'h:i A'];

    protected $fillable = ['locale', 'timezone', 'date_format', 'time_format'];

    protected static function booted(): void
    {
        static::saved(fn () => Cache::forget(self::CACHE_KEY));
        static::deleted(fn () => Cache::forget(self::CACHE_KEY));
    }

    public static function current(): self
    {
        try {
            $attributes = Cache::rememberForever(self::CACHE_KEY, fn () => static::query()->first()?->getAttributes() ?? []);
        } catch (QueryException) {
            return static::defaults();
        }

        if (! is_array($attributes) || $attributes === []) {
            return static::defaults();
        }

        return (new static)->newFromBuilder($attributes);
    }

    public static function defaults(): self
    {
        return new static([
            'locale' => config('app.locale', 'vi'),
            'timezone' => config('app.timezone', 'Asia/Ho_Chi_Minh'),
            'date_format' => 'd/m/Y',
            'time_format' => 'H:i',
        ]);
    }

    public static function activeLocale(): string
    {
        $locale = static::current()->locale;

        return array_key_exists($locale, self::LOCALES) ? $locale : config('app.fallback_locale', 'vi');
    }
}
