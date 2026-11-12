<?php

namespace App\Http\Middleware;

use App\Support\I18n\PortalLocale;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class StripPortalLocalePrefix
{
    public function handle(Request $request, Closure $next): Response
    {
        $path = $request->getPathInfo();

        foreach (PortalLocale::prefixed() as $locale) {
            if ($path !== '/'.$locale && ! str_starts_with($path, '/'.$locale.'/')) {
                continue;
            }

            $uri = $request->server->get('REQUEST_URI', $path);
            $stripped = preg_replace('#^(.*?)/'.preg_quote($locale, '#').'(?=/|\?|$)#', '$1', $uri, 1);
            if ($stripped === '' || $stripped[0] === '?') {
                $stripped = '/'.$stripped;
            }

            $request->server->set('REQUEST_URI', $stripped);
            $request = $request->duplicate(server: $request->server->all());
            $request->attributes->set(PortalLocale::PREFIX_ATTRIBUTE, $locale);
            app()->instance('request', $request);

            break;
        }

        return $next($request);
    }
}
