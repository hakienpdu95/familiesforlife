<?php

namespace App\Services\Monitoring;

use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Queue;
use Illuminate\Support\Facades\Redis;
use Laravel\Horizon\Contracts\JobRepository;
use Laravel\Horizon\Contracts\MasterSupervisorRepository;
use Laravel\Horizon\Contracts\MetricsRepository;
use Throwable;

class SystemMonitorService
{
    public const RANGES = [60 => 1, 360 => 5, 1440 => 15];

    public function snapshot(): array
    {
        return Cache::remember('monitor:snapshot', 10, fn () => [
            'server' => $this->server(),
            'database' => $this->database(),
            'redis' => $this->redisInfo(),
            'queue' => $this->queue(),
            'requests' => $this->requestSummary(),
            'at' => now()->toIso8601String(),
        ]);
    }

    public function series(int $minutes): array
    {
        $minutes = array_key_exists($minutes, self::RANGES) ? $minutes : 60;
        $step = self::RANGES[$minutes];
        $now = time();
        $start = $now - ($minutes - 1) * 60;

        $timestamps = range($start - ($start % 60), $now, 60);
        $buckets = $this->readBuckets($timestamps);

        $labels = $rpm = $avg = $errors = [];
        foreach (array_chunk($timestamps, $step) as $chunk) {
            $count = $total = $err = 0;
            foreach ($chunk as $ts) {
                $count += $buckets[$ts]['count'];
                $total += $buckets[$ts]['total_ms'];
                $err += $buckets[$ts]['errors'];
            }
            $labels[] = date($minutes > 60 ? 'H:i' : 'H:i', $chunk[0]);
            $rpm[] = round($count / count($chunk), 1);
            $avg[] = $count > 0 ? (int) round($total / $count) : 0;
            $errors[] = $err;
        }

        return [
            'minutes' => $minutes,
            'step' => $step,
            'labels' => $labels,
            'rpm' => $rpm,
            'avg_ms' => $avg,
            'errors' => $errors,
        ];
    }

    public function slowRequests(): array
    {
        return $this->readList(MetricsRecorder::SLOW_REQUESTS);
    }

    public function slowQueries(): array
    {
        return $this->readList(MetricsRecorder::SLOW_QUERIES);
    }

    private function server(): array
    {
        $cores = max(1, (int) (@file_exists('/proc/cpuinfo') ? substr_count((string) @file_get_contents('/proc/cpuinfo'), 'processor') : 1));
        $load = function_exists('sys_getloadavg') ? (sys_getloadavg() ?: [0, 0, 0]) : [0, 0, 0];

        $memTotal = $memAvailable = null;
        if (is_readable('/proc/meminfo')) {
            $info = (string) file_get_contents('/proc/meminfo');
            preg_match('/MemTotal:\s+(\d+)/', $info, $t);
            preg_match('/MemAvailable:\s+(\d+)/', $info, $a);
            $memTotal = isset($t[1]) ? (int) $t[1] * 1024 : null;
            $memAvailable = isset($a[1]) ? (int) $a[1] * 1024 : null;
        }

        $diskTotal = @disk_total_space(storage_path()) ?: 0;
        $diskFree = @disk_free_space(storage_path()) ?: 0;

        return [
            'cores' => $cores,
            'load' => array_map(fn ($l) => round($l, 2), $load),
            'cpu_pct' => min(100, (int) round($load[0] / $cores * 100)),
            'mem_total' => $memTotal,
            'mem_used' => $memTotal !== null && $memAvailable !== null ? $memTotal - $memAvailable : null,
            'mem_pct' => $memTotal ? (int) round(($memTotal - $memAvailable) / $memTotal * 100) : null,
            'disk_total' => $diskTotal,
            'disk_used' => $diskTotal - $diskFree,
            'disk_pct' => $diskTotal > 0 ? (int) round(($diskTotal - $diskFree) / $diskTotal * 100) : null,
            'php' => PHP_VERSION,
            'laravel' => app()->version(),
            'environment' => app()->environment(),
        ];
    }

    private function database(): array
    {
        return $this->attempt(function () {
            $connection = DB::connection();
            $driver = $connection->getDriverName();

            if (! in_array($driver, ['mysql', 'mariadb'], true)) {
                return ['driver' => $driver, 'supported' => false];
            }

            $status = collect($connection->select(
                "SHOW GLOBAL STATUS WHERE Variable_name IN ('Threads_connected','Threads_running','Max_used_connections','Slow_queries','Uptime','Questions','Aborted_connects')"
            ))->pluck('Value', 'Variable_name');

            $variables = collect($connection->select(
                "SHOW VARIABLES WHERE Variable_name IN ('max_connections','long_query_time','version')"
            ))->pluck('Value', 'Variable_name');

            $size = Cache::remember('monitor:db-size', 300, fn () => (int) $connection->selectOne(
                'SELECT COALESCE(SUM(data_length + index_length), 0) AS size FROM information_schema.tables WHERE table_schema = ?',
                [$connection->getDatabaseName()]
            )->size);

            $max = (int) ($variables['max_connections'] ?? 0);
            $connected = (int) ($status['Threads_connected'] ?? 0);

            return [
                'driver' => $driver,
                'supported' => true,
                'version' => $variables['version'] ?? null,
                'connected' => $connected,
                'running' => (int) ($status['Threads_running'] ?? 0),
                'max_connections' => $max,
                'max_used' => (int) ($status['Max_used_connections'] ?? 0),
                'usage_pct' => $max > 0 ? (int) round($connected / $max * 100) : null,
                'slow_queries' => (int) ($status['Slow_queries'] ?? 0),
                'long_query_time' => (float) ($variables['long_query_time'] ?? 0),
                'aborted_connects' => (int) ($status['Aborted_connects'] ?? 0),
                'uptime' => (int) ($status['Uptime'] ?? 0),
                'size' => $size,
            ];
        });
    }

    private function redisInfo(): array
    {
        return $this->attempt(function () {
            $info = Redis::connection(config('monitoring.redis_connection', 'default'))->info();
            $flat = [];
            array_walk_recursive($info, function ($value, $key) use (&$flat) {
                $flat[$key] = $value;
            });

            $hits = (int) ($flat['keyspace_hits'] ?? 0);
            $misses = (int) ($flat['keyspace_misses'] ?? 0);

            return [
                'version' => $flat['redis_version'] ?? null,
                'memory' => $flat['used_memory_human'] ?? null,
                'clients' => (int) ($flat['connected_clients'] ?? 0),
                'hit_rate' => ($hits + $misses) > 0 ? round($hits / ($hits + $misses) * 100, 1) : null,
                'uptime_days' => (int) ($flat['uptime_in_days'] ?? 0),
            ];
        });
    }

    private function queue(): array
    {
        $connection = config('queue.default');
        $queues = collect(config('horizon.defaults', []))->pluck('queue')->flatten()->unique()->values()->all() ?: ['default'];

        $sizes = [];
        foreach ($queues as $name) {
            $sizes[$name] = $this->attempt(fn () => Queue::connection($connection)->size($name), null);
        }

        $horizon = $this->attempt(function () {
            $masters = collect(app(MasterSupervisorRepository::class)->all());

            return [
                'status' => $masters->isEmpty() ? 'inactive' : ($masters->contains(fn ($m) => $m->status === 'paused') ? 'paused' : 'running'),
                'processes' => $masters->sum(fn ($m) => collect($m->supervisors ?? [])->count()),
                'jobs_per_minute' => (int) app(MetricsRepository::class)->jobsProcessedPerMinute(),
                'recent' => (int) app(JobRepository::class)->countRecent(),
                'recent_failed' => (int) app(JobRepository::class)->countRecentlyFailed(),
                'pending' => (int) app(JobRepository::class)->countPending(),
            ];
        }, ['status' => 'unknown']);

        return [
            'connection' => $connection,
            'sizes' => $sizes,
            'failed' => $this->attempt(fn () => DB::table(config('queue.failed.table', 'failed_jobs'))->count(), null),
            'horizon' => $horizon,
        ];
    }

    private function requestSummary(): array
    {
        $now = time();
        $buckets = $this->readBuckets(range($now - 59 * 60 - ($now % 60), $now, 60));

        $sum = function (int $minutes) use ($buckets) {
            $slice = array_slice($buckets, -$minutes, null, true);
            $count = array_sum(array_column($slice, 'count'));
            $total = array_sum(array_column($slice, 'total_ms'));

            return [
                'count' => $count,
                'rpm' => round($count / $minutes, 1),
                'avg_ms' => $count > 0 ? (int) round($total / $count) : null,
                'errors' => array_sum(array_column($slice, 'errors')),
            ];
        };

        return [
            'last_5m' => $sum(5),
            'last_60m' => $sum(60),
            'slow_request_ms' => config('monitoring.slow_request_ms'),
            'slow_query_ms' => config('monitoring.slow_query_ms'),
        ];
    }

    private function readBuckets(array $timestamps): array
    {
        $empty = ['count' => 0, 'total_ms' => 0.0, 'errors' => 0];

        $rows = $this->attempt(fn () => Redis::connection(config('monitoring.redis_connection', 'default'))
            ->pipeline(function ($pipe) use ($timestamps) {
                foreach ($timestamps as $ts) {
                    $pipe->hgetall(MetricsRecorder::bucketKey($ts));
                }
            }), []);

        $result = [];
        foreach ($timestamps as $i => $ts) {
            $row = $rows[$i] ?? [];
            $result[$ts] = [
                'count' => (int) ($row['count'] ?? 0),
                'total_ms' => (float) ($row['total_ms'] ?? 0),
                'errors' => (int) ($row['errors'] ?? 0),
            ] + $empty;
        }

        return $result;
    }

    private function readList(string $key): array
    {
        return $this->attempt(fn () => collect(Redis::connection(config('monitoring.redis_connection', 'default'))->lrange($key, 0, -1))
            ->map(fn ($item) => json_decode($item, true))
            ->filter()
            ->values()
            ->all(), []);
    }

    private function attempt(callable $callback, mixed $fallback = ['error' => true]): mixed
    {
        try {
            return $callback();
        } catch (Throwable $e) {
            report($e);

            return is_array($fallback) && isset($fallback['error']) ? ['error' => $e->getMessage()] : $fallback;
        }
    }
}
