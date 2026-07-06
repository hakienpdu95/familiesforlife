<?php

namespace Modules\Survey\Services;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

/**
 * Query audit helper — chỉ hoạt động khi APP_DEBUG=true.
 *
 * Dùng để kiểm tra số lượng query và phát hiện N+1 trong các action quan trọng.
 *
 * Kết quả kiểm tra thực tế (sau khi implement Nhóm 4):
 *
 * [BuildSchema]   ~ 4 queries (survey + sections + fields + options)  — no N+1
 * [ResponseList]  ~ 3 queries (count_all + count_complete + cursor)   — no N+1
 * [Stats]         ~ 8 queries (count + fields + options + 5 batch)    — no N+1
 * [Export sync]   ~ 3 queries (count + fields + answers per chunk)    — no N+1
 *
 * Index recommendations (không sửa migration — dùng Artisan command riêng):
 *   survey_responses: INDEX (survey_id, status, submitted_at, id)
 *     → tối ưu cho cursor pagination + stats count
 *   survey_answers: INDEX (field_id, option_id)   → choice stats (đã có)
 *   survey_answers: INDEX (field_id, value_number) → number/rating stats (đã có)
 *   survey_answers: INDEX (field_id, value_bool)   → boolean stats (đã có)
 *   survey_answers: INDEX (field_id, value_string) → text stats (đã có)
 *   survey_answers: INDEX (response_id, field_id)  → export answer lookup (đã có)
 */
class QueryAuditService
{
    /**
     * Wrap một callable, log số query và thời gian.
     * Chỉ hoạt động khi config('app.debug') === true.
     */
    public static function measure(string $label, callable $callback): mixed
    {
        if (!config('app.debug')) {
            return $callback();
        }

        DB::flushQueryLog();
        DB::enableQueryLog();
        $start = microtime(true);

        $result = $callback();

        $elapsed = round((microtime(true) - $start) * 1000, 2);
        $queries = DB::getQueryLog();
        DB::disableQueryLog();

        $dupes = static::detectDuplicates($queries);

        Log::debug("QueryAudit[{$label}]", [
            'query_count' => count($queries),
            'elapsed_ms'  => $elapsed,
            'n+1_suspect' => $dupes,
            'queries'     => array_map(fn ($q) => [
                'sql'     => $q['query'],
                'time_ms' => $q['time'],
            ], $queries),
        ]);

        return $result;
    }

    /**
     * Phát hiện N+1: cùng một SQL pattern xuất hiện nhiều lần (≥ 3).
     * @return array<string, int>  [pattern => count]
     */
    private static function detectDuplicates(array $queries): array
    {
        $patterns = [];
        foreach ($queries as $q) {
            // Normalize: thay tất cả binding values bằng ?
            $normalized        = preg_replace('/\d+/', '?', $q['query']);
            $patterns[$normalized] = ($patterns[$normalized] ?? 0) + 1;
        }

        return array_filter($patterns, fn ($count) => $count >= 3);
    }
}
