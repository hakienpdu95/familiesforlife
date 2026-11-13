<?php

namespace App\Shared\Traceability\Models;

use App\Shared\Traceability\Enums\QrScanSource;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/** Thống kê nội bộ — KHÔNG hiển thị trên trang truy xuất công khai. */
class QrScan extends Model
{
    public const DEDUPE_MINUTES = 30;

    public $timestamps = false;

    protected $table = 'qr_scans';

    protected $fillable = ['uuid', 'qr_identity_id', 'visitor_hash', 'user_agent', 'scan_source', 'locale', 'scanned_at'];

    protected $casts = ['scanned_at' => 'datetime', 'scan_source' => QrScanSource::class];

    public const DEVICES = [
        'mobile' => 'Điện thoại',
        'tablet' => 'Máy tính bảng',
        'desktop' => 'Máy tính',
    ];

    public const PLATFORMS = [
        'ios' => 'iOS',
        'android' => 'Android',
        'windows' => 'Windows',
        'macos' => 'macOS',
        'other' => 'Khác',
    ];

    private const DEVICE_RULES = [
        'tablet' => 'ipad|tablet',
        'mobile' => 'mobi|iphone|ipod|android',
    ];

    private const PLATFORM_RULES = [
        'ios' => 'iphone|ipad|ipod',
        'android' => 'android',
        'windows' => 'windows',
        'macos' => 'macintosh|mac os',
    ];

    public static function deviceOf(?string $userAgent): string
    {
        return static::match((string) $userAgent, self::DEVICE_RULES, 'desktop');
    }

    public static function platformOf(?string $userAgent): string
    {
        return static::match((string) $userAgent, self::PLATFORM_RULES, 'other');
    }

    public static function deviceSql(): string
    {
        return static::caseSql(self::DEVICE_RULES, 'desktop');
    }

    public static function platformSql(): string
    {
        return static::caseSql(self::PLATFORM_RULES, 'other');
    }

    private static function match(string $userAgent, array $rules, string $fallback): string
    {
        foreach ($rules as $key => $pattern) {
            if (preg_match('/'.$pattern.'/i', $userAgent)) {
                return $key;
            }
        }

        return $fallback;
    }

    private static function caseSql(array $rules, string $fallback): string
    {
        $cases = collect($rules)->map(fn ($pattern, $key) => "WHEN LOWER(COALESCE(user_agent, '')) REGEXP '{$pattern}' THEN '{$key}'")->implode(' ');

        return "CASE {$cases} ELSE '{$fallback}' END";
    }

    public function identity(): BelongsTo
    {
        return $this->belongsTo(QrIdentity::class, 'qr_identity_id');
    }
}
