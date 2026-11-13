<?php

namespace App\Support\Analytics;

use Illuminate\Support\HtmlString;
use Illuminate\Support\Str;

class ClickTracking
{
    public const SCRIPT = 'resources/js/shared/click-tracker.js';

    public const BUILD_DIRECTORY = 'build/frontend';

    private static ?bool $scriptBuilt = null;

    public static function scriptBuilt(): bool
    {
        if (static::$scriptBuilt !== null) {
            return static::$scriptBuilt;
        }

        $manifest = public_path(self::BUILD_DIRECTORY.'/manifest.json');
        $entries = is_file($manifest) ? json_decode((string) file_get_contents($manifest), true) : null;

        return static::$scriptBuilt = is_array($entries) && isset($entries[self::SCRIPT]);
    }

    public static function context(?string $placement = null, ?string $item = null, string|int|null $itemId = null, ?string $category = null): HtmlString
    {
        return static::attributes([
            'data-track-placement' => $placement,
            'data-track-item' => $item !== null ? Str::limit(trim(strip_tags($item)), 100, '') : null,
            'data-track-item-id' => $itemId,
            'data-track-item-category' => $category,
        ]);
    }

    public static function link(?string $platform = null, ?string $event = null, ?string $label = null, array $params = []): HtmlString
    {
        return static::attributes([
            'data-track' => $event,
            'data-track-platform' => $platform,
            'data-track-label' => $label,
            ...static::paramAttributes($params),
        ]);
    }

    public static function view(string $event, array $params = []): HtmlString
    {
        return static::attributes([
            'data-track-view' => $event,
            ...static::paramAttributes($params),
        ]);
    }

    public static function toggle(string $event, ?string $section = null, array $params = []): HtmlString
    {
        return static::attributes([
            'data-track-toggle' => $event,
            'data-track-section' => $section,
            ...static::paramAttributes($params),
        ]);
    }

    private static function paramAttributes(array $params): array
    {
        return collect($params)
            ->mapWithKeys(fn ($value, $key) => ['data-track-param-'.Str::kebab(Str::camel((string) $key)) => is_bool($value) ? ($value ? 'true' : 'false') : $value])
            ->all();
    }

    public static function clientConfig(): array
    {
        $config = config('click_tracking', []);

        return [
            'events' => $config['events'] ?? [],
            'platforms' => $config['platforms'] ?? [],
            'commerce' => array_values($config['commerce_platforms'] ?? []),
            'contact' => array_values($config['contact_platforms'] ?? []),
        ];
    }

    private static function attributes(array $attributes): HtmlString
    {
        return new HtmlString(collect($attributes)
            ->reject(fn ($value) => $value === null || $value === '')
            ->map(fn ($value, $name) => $name.'="'.e((string) $value).'"')
            ->implode(' '));
    }
}
