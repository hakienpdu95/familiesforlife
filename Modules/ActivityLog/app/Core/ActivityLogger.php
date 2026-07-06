<?php

namespace Modules\ActivityLog\Core;

use Modules\ActivityLog\Actions\WriteActivityLogAction;
use Modules\ActivityLog\Enums\LogLevel;

final class ActivityLogger
{
    public static function log(
        string   $module,
        string   $action,
        mixed    $subject     = null,
        array    $context     = [],
        LogLevel $level       = LogLevel::Info,
        ?string  $description = null,
    ): void {
        if ($level->value < config('activitylog_module.min_level', LogLevel::Info->value)) {
            return;
        }

        try {
            $entry = app(LogEntryBuilder::class)->build(
                $module, $action, $subject, $context, $level, $description
            );
            // Ghi đồng bộ — không dùng queue để đơn giản hoá hệ thống
            WriteActivityLogAction::run($entry);
        } catch (\Throwable $e) {
            logger()->error('[ActivityLog] write failed', [
                'module' => $module,
                'action' => $action,
                'error'  => $e->getMessage(),
            ]);
        }
    }

    public static function info(string $m, string $a, mixed $s = null, array $c = []): void
    {
        self::log($m, $a, $s, $c, LogLevel::Info);
    }

    public static function warning(string $m, string $a, mixed $s = null, array $c = []): void
    {
        self::log($m, $a, $s, $c, LogLevel::Warning);
    }

    public static function error(
        string $m,
        string $a,
        mixed  $s    = null,
        array  $c    = [],
        string $desc = '',
    ): void {
        self::log($m, $a, $s, $c, LogLevel::Error, $desc ?: null);
    }

    public static function critical(string $m, string $a, mixed $s = null, array $c = []): void
    {
        self::log($m, $a, $s, $c, LogLevel::Critical);
    }
}
