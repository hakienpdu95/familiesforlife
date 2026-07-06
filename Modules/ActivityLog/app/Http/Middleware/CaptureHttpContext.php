<?php

namespace Modules\ActivityLog\Http\Middleware;

use Illuminate\Support\Facades\Cache;
use Symfony\Component\HttpFoundation\Response;

class CaptureHttpContext
{
    // TTL đủ lâu cho job queue xử lý trong điều kiện bình thường.
    // Nếu queue bận > TTL, status_code/duration_ms sẽ là NULL — acceptable.
    private const CACHE_TTL_SECONDS = 60;

    public function handle(\Illuminate\Http\Request $request, \Closure $next): Response
    {
        $startMs  = (int)(microtime(true) * 1000);
        $response = $next($request);

        if ($requestId = $request->header('X-Request-Id')) {
            Cache::put("actlog:http_ctx:{$requestId}", [
                'status_code' => $response->getStatusCode(),
                'duration_ms' => (int)(microtime(true) * 1000) - $startMs,
            ], self::CACHE_TTL_SECONDS);
        }

        return $response;
    }
}
