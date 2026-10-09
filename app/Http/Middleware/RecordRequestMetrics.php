<?php

namespace App\Http\Middleware;

use App\Services\Monitoring\MetricsRecorder;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class RecordRequestMetrics
{
    public function __construct(
        private readonly MetricsRecorder $recorder,
    ) {}

    public function handle(Request $request, Closure $next): Response
    {
        return $next($request);
    }

    public function terminate(Request $request, Response $response): void
    {
        $startedAt = defined('LARAVEL_START') ? LARAVEL_START : $request->server('REQUEST_TIME_FLOAT');

        if ($startedAt) {
            $this->recorder->recordRequest($request, $response->getStatusCode(), (microtime(true) - $startedAt) * 1000);
        }
    }
}
