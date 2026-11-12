<?php

namespace App\Http\Middleware;

use App\Support\I18n\PortalLocale;
use App\Support\SystemLocale;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\App;
use Symfony\Component\HttpFoundation\Response;

class ApplySystemLocale
{
    public function handle(Request $request, Closure $next): Response
    {
        $portal = app(PortalLocale::class);

        $portal->active() ? App::setLocale($portal->current()) : SystemLocale::apply();

        return $next($request);
    }
}
