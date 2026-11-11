<?php

namespace App\Http\Middleware;

use App\Support\SystemLocale;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class ApplySystemLocale
{
    public function handle(Request $request, Closure $next): Response
    {
        SystemLocale::apply();

        return $next($request);
    }
}
