<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class RemoveServerHeaders
{
    public function handle(Request $request, Closure $next): Response
    {
        // Remove headers PHP SAPI injects before Symfony response is sent
        header_remove('X-Powered-By');
        header_remove('Server');

        $response = $next($request);

        $response->headers->remove('X-Powered-By');
        $response->headers->remove('Server');

        return $response;
    }
}
