<?php

namespace App\Support\I18n;

use Closure;
use Illuminate\Routing\Route;
use Illuminate\Support\Facades\App;
use Illuminate\Support\Str;

class PortalLocale
{
    public const PREFIX_ATTRIBUTE = 'portal_locale_prefix';

    private ?string $locale = null;

    private ?string $urlLocale = null;

    private bool $translated = false;

    private ?array $alternates = null;

    private array $routeCache = [];

    public static function supported(): array
    {
        return config('i18n.portal_locales', ['vi']);
    }

    public static function default(): string
    {
        return config('i18n.portal_default_locale', 'vi');
    }

    public static function prefixed(): array
    {
        return array_values(array_diff(self::supported(), [self::default()]));
    }

    public static function label(string $locale): string
    {
        return config("i18n.portal_locale_labels.{$locale}", strtoupper($locale));
    }

    public function isPortalRoute(?Route $route): bool
    {
        $name = $route?->getName();

        if ($name === null) {
            return false;
        }

        return $this->routeCache[$name] ??= Str::is(config('i18n.portal_routes', []), $name)
            && ! Str::is(config('i18n.portal_routes_except', []), $name);
    }

    public function activate(string $locale): void
    {
        $this->locale = in_array($locale, self::supported(), true) ? $locale : self::default();
        $this->urlLocale = $this->locale;
        App::setLocale($this->locale);
    }

    public function active(): bool
    {
        return $this->locale !== null;
    }

    public function current(): string
    {
        return $this->locale ?? self::default();
    }

    public function isDefault(?string $locale = null): bool
    {
        return ($locale ?? $this->current()) === self::default();
    }

    public function urlLocale(): string
    {
        return $this->urlLocale ?? self::default();
    }

    public function using(string $locale, Closure $callback): mixed
    {
        $previous = $this->urlLocale;
        $this->urlLocale = $locale;

        try {
            return $callback();
        } finally {
            $this->urlLocale = $previous;
        }
    }

    public function prefix(?string $locale = null): string
    {
        $locale ??= $this->urlLocale();

        return $locale === self::default() ? '' : $locale;
    }

    public function applyPrefix(string $url, ?string $locale = null): string
    {
        $prefix = $this->prefix($locale);

        if ($prefix === '' || ! preg_match('#^(https?://[^/?\#]+)?(.*)$#s', $url, $m)) {
            return $url;
        }

        $rest = $m[2] === '/' ? '' : $m[2];

        return $m[1].'/'.$prefix.$rest;
    }

    public function localizeInternalUrl(?string $url): ?string
    {
        if ($url === null || $this->prefix() === '') {
            return $url;
        }

        $host = parse_url($url, PHP_URL_HOST);
        if ($host !== null && $host !== request()->getHost()) {
            return $url;
        }
        if ($host === null && ! str_starts_with($url, '/')) {
            return $url;
        }

        $path = parse_url($url, PHP_URL_PATH) ?? '/';
        $prefix = '/'.$this->prefix();
        if ($path === $prefix || str_starts_with($path, $prefix.'/')) {
            return $url;
        }

        return $this->applyPrefix($url);
    }

    public function markTranslated(bool $translated = true): static
    {
        $this->translated = $translated;

        return $this;
    }

    public function isTranslated(): bool
    {
        return $this->isDefault() || $this->translated;
    }

    public function showFallbackNotice(): bool
    {
        return $this->active() && ! $this->isTranslated();
    }

    public function setAlternates(array $urls): static
    {
        $this->alternates = array_filter($urls);

        return $this;
    }

    public function urlFor(string $locale): ?string
    {
        $route = request()->route();

        if (! $this->isPortalRoute($route)) {
            return null;
        }

        return $this->using($locale, fn () => route($route->getName(), $route->parameters()));
    }

    public function alternates(): array
    {
        if ($this->alternates !== null) {
            return $this->alternates;
        }

        $locales = $this->translated ? self::supported() : [self::default()];
        $urls = [];
        foreach ($locales as $locale) {
            $urls[$locale] = $this->urlFor($locale);
        }

        return array_filter($urls);
    }

    public function hreflangs(): array
    {
        if (! $this->active() || ! $this->isTranslated()) {
            $default = $this->alternates()[self::default()] ?? null;

            return $default ? [self::default() => $default, 'x-default' => $default] : [];
        }

        $alternates = $this->alternates();
        if (isset($alternates[self::default()])) {
            $alternates['x-default'] = $alternates[self::default()];
        }

        return $alternates;
    }

    public function switcherLinks(): array
    {
        $alternates = $this->alternates();
        $links = [];

        foreach (self::supported() as $locale) {
            $url = $alternates[$locale]
                ?? ($locale === $this->current() ? url()->full() : $this->using($locale, fn () => route('post.public.home')));

            $links[$locale] = ['label' => self::label($locale), 'url' => $url, 'active' => $locale === $this->current()];
        }

        return $links;
    }

    public function originalUrl(): ?string
    {
        return $this->alternates()[self::default()] ?? null;
    }
}
