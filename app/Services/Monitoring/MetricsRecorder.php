<?php

namespace App\Services\Monitoring;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Redis;
use Illuminate\Support\Str;
use Throwable;

class MetricsRecorder
{
    public const SLOW_REQUESTS = 'monitor:slow_requests';

    public const SLOW_QUERIES = 'monitor:slow_queries';

    private bool $recordingQuery = false;

    public static function bucketKey(int $timestamp): string
    {
        return 'monitor:req:'.date('YmdHi', $timestamp);
    }

    public function recordRequest(Request $request, int $status, float $durationMs): void
    {
        if (! config('monitoring.enabled') || $request->is(...config('monitoring.ignore_paths', []))) {
            return;
        }

        $this->safely(function () use ($request, $status, $durationMs) {
            $redis = $this->redis();
            $key = self::bucketKey(time());
            $ttl = config('monitoring.retention_hours', 26) * 3600;

            $redis->pipeline(function ($pipe) use ($key, $status, $durationMs, $ttl) {
                $pipe->hincrby($key, 'count', 1);
                $pipe->hincrbyfloat($key, 'total_ms', round($durationMs, 2));
                if ($status >= 500) {
                    $pipe->hincrby($key, 'errors', 1);
                }
                $pipe->expire($key, $ttl);
            });

            if ($durationMs >= config('monitoring.slow_request_ms', 1000)) {
                $this->pushCapped(self::SLOW_REQUESTS, [
                    'method' => $request->method(),
                    'path' => '/'.ltrim($request->path(), '/'),
                    'route' => $request->route()?->getName(),
                    'status' => $status,
                    'ms' => (int) round($durationMs),
                    'user_id' => $request->user()?->id,
                    'at' => now()->toIso8601String(),
                ]);
            }
        });
    }

    public function recordQuery(string $sql, float $timeMs, string $connection): void
    {
        if ($this->recordingQuery || ! config('monitoring.enabled') || $timeMs < config('monitoring.slow_query_ms', 500)) {
            return;
        }

        $this->recordingQuery = true;

        try {
            $this->safely(fn () => $this->pushCapped(self::SLOW_QUERIES, [
                'sql' => Str::limit(preg_replace('/\s+/', ' ', $sql), 500),
                'ms' => (int) round($timeMs),
                'connection' => $connection,
                'at' => now()->toIso8601String(),
            ]));
        } finally {
            $this->recordingQuery = false;
        }
    }

    private function pushCapped(string $key, array $entry): void
    {
        $size = config('monitoring.slow_list_size', 50);

        $this->redis()->pipeline(function ($pipe) use ($key, $entry, $size) {
            $pipe->lpush($key, json_encode($entry, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES));
            $pipe->ltrim($key, 0, $size - 1);
        });
    }

    private function redis()
    {
        return Redis::connection(config('monitoring.redis_connection', 'default'));
    }

    private function safely(callable $callback): void
    {
        try {
            $callback();
        } catch (Throwable $e) {
            Log::debug('[monitoring] bỏ qua ghi số liệu: '.$e->getMessage());
        }
    }
}
