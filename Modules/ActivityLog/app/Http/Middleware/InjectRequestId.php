<?php

namespace Modules\ActivityLog\Http\Middleware;

use Illuminate\Support\Str;
use Symfony\Component\HttpFoundation\Response;

class InjectRequestId
{
    public function handle(\Illuminate\Http\Request $request, \Closure $next): Response
    {
        $requestId = $request->header('X-Request-Id', (string) Str::uuid());
        $request->headers->set('X-Request-Id', $requestId);

        $response = $next($request);
        $response->headers->set('X-Request-Id', $requestId);

        return $response;
    }
}
