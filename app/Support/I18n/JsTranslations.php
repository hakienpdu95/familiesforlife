<?php

namespace App\Support\I18n;

use Illuminate\Support\Facades\App;
use Illuminate\Support\Facades\Cache;

class JsTranslations
{
    private const CACHE_PREFIX = 'i18n.js.';

    /** @return array<string, string> */
    public static function for(?string $locale = null): array
    {
        $locale ??= App::getLocale();
        $path = lang_path($locale.'.json');

        if ($locale === config('i18n.source_locale') || ! is_file($path)) {
            return [];
        }

        return Cache::rememberForever(self::CACHE_PREFIX.$locale.'.'.filemtime($path), function () use ($path) {
            $dictionary = json_decode(file_get_contents($path), true) ?: [];
            $keys = app(TranslationKeyScanner::class)->jsKeys();

            return array_filter(
                array_intersect_key($dictionary, $keys),
                fn ($value, $key) => $value !== $key,
                ARRAY_FILTER_USE_BOTH,
            );
        });
    }

    public static function flush(): void
    {
        foreach (array_merge([config('i18n.source_locale')], config('i18n.target_locales', [])) as $locale) {
            $path = lang_path($locale.'.json');
            if (is_file($path)) {
                Cache::forget(self::CACHE_PREFIX.$locale.'.'.filemtime($path));
            }
        }
    }
}
