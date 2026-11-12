<?php

namespace App\Routing;

use App\Support\I18n\PortalLocale;
use Illuminate\Routing\UrlGenerator;

class LocalizedUrlGenerator extends UrlGenerator
{
    public function toRoute($route, $parameters, $absolute)
    {
        $url = parent::toRoute($route, $parameters, $absolute);
        $portal = app(PortalLocale::class);

        if ($portal->prefix() === '' || ! $portal->isPortalRoute($route)) {
            return $url;
        }

        return $portal->applyPrefix($url);
    }

    public function current()
    {
        return $this->localizeCurrent(parent::current());
    }

    public function full()
    {
        return $this->localizeCurrent(parent::full());
    }

    private function localizeCurrent(string $url): string
    {
        $portal = app(PortalLocale::class);

        if (! $portal->active() || $portal->isDefault($portal->current())) {
            return $url;
        }

        return $portal->applyPrefix($url, $portal->current());
    }
}
