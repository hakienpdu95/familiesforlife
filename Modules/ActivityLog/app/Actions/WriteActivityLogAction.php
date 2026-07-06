<?php

namespace Modules\ActivityLog\Actions;

use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Lorisleiva\Actions\Concerns\AsAction;
use Modules\ActivityLog\Data\LogEntryData;

class WriteActivityLogAction
{
    use AsAction;

    public function handle(LogEntryData $entry): void
    {
        DB::transaction(function () use ($entry) {
            $logId = $this->writeMainLog($entry);
            $this->writeContexts($logId, $entry->context);
            $this->writeHttp($logId, $entry);
        });
    }

    private function writeMainLog(LogEntryData $entry): int
    {
        return DB::table('activity_log')->insertGetId([
            'log_name'        => "{$entry->module}.{$entry->action}",
            'description'     => $entry->description ?? $entry->action,
            'subject_type'    => $entry->subjectType,
            'subject_id'      => $entry->subjectId,
            'causer_type'     => $entry->actorId ? \App\Models\User::class : null,
            'causer_id'       => $entry->actorId,
            'event'           => $entry->action,
            'organization_id' => $entry->organizationId,
            'level'           => $entry->level->value,
            'module'          => $entry->module,
            'action'          => $entry->action,
            'actor_name'      => $entry->actorName,
            'actor_ip'        => $entry->actorIp,
            'request_id'      => $entry->requestId,
            'session_id'      => $entry->sessionId,
            'subject_label'   => $entry->subjectLabel,
            'created_at'      => $entry->loggedAt->format('Y-m-d H:i:s'),
            'updated_at'      => now(),
        ]);
    }

    private function writeContexts(int $logId, array $context): void
    {
        if (empty($context)) return;

        $rows = [];
        foreach ($context as $key => $value) {
            $rows[] = $this->buildContextRow($logId, $key, $value);
        }
        DB::table('activity_log_contexts')->insert($rows);
    }

    private function writeHttp(int $logId, LogEntryData $entry): void
    {
        if ($entry->http === null) return;

        // Ghi đồng bộ — status_code/duration_ms được enrich bởi CaptureHttpContext middleware
        // thông qua cache. Khi ghi đồng bộ trong request, cache chưa được set nên sẽ là NULL.
        $cached = Cache::pull("actlog:http_ctx:{$entry->requestId}");

        DB::table('activity_log_http')->insert([
            'log_id'      => $logId,
            'http_method' => $entry->http->method->value,
            'url'         => $entry->http->url,
            'route_name'  => $entry->http->routeName,
            'status_code' => $cached['status_code'] ?? null,
            'duration_ms' => $cached['duration_ms'] ?? null,
            'user_agent'  => $entry->http->userAgent,
            'created_at'  => now(),
        ]);
    }

    private function buildContextRow(int $logId, string $key, mixed $value): array
    {
        $row = [
            'log_id'       => $logId,
            'key_name'     => substr($key, 0, 64),
            'value_type'   => 1,
            'val_string'   => null,
            'val_integer'  => null,
            'val_decimal'  => null,
            'val_boolean'  => null,
            'val_datetime' => null,
            'created_at'   => now(),
        ];

        if (is_bool($value)) {
            $row['value_type'] = 4;
            $row['val_boolean'] = (int) $value;
        } elseif (is_int($value)) {
            $row['value_type'] = 2;
            $row['val_integer'] = $value;
        } elseif (is_float($value)) {
            $row['value_type'] = 3;
            $row['val_decimal'] = $value;
        } elseif ($value instanceof \DateTimeInterface) {
            $row['value_type'] = 5;
            $row['val_datetime'] = $value->format('Y-m-d H:i:s');
        } elseif ($value instanceof \BackedEnum) {
            $v = $value->value;
            if (is_int($v)) {
                $row['value_type'] = 2;
                $row['val_integer'] = $v;
            } else {
                $row['value_type'] = 1;
                $row['val_string'] = substr((string) $v, 0, 500);
            }
        } else {
            $row['value_type'] = 1;
            $row['val_string'] = substr((string) $value, 0, 500);
        }

        return $row;
    }
}
