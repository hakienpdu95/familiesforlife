<?php

namespace App\Shared\Traceability\Support;

final class QrScanUserAgent
{
    public const APPS = [
        'zalo' => 'Zalo',
        'facebook' => 'Facebook',
        'messenger' => 'Messenger',
        'instagram' => 'Instagram',
        'tiktok' => 'TikTok',
        'line' => 'LINE',
        'wechat' => 'WeChat',
        'google' => 'Google / Google Lens',
        'safari' => 'Safari',
        'chrome' => 'Chrome',
        'samsung' => 'Samsung Internet',
        'coccoc' => 'Cốc Cốc',
        'edge' => 'Edge',
        'firefox' => 'Firefox',
        'opera' => 'Opera',
        'xiaomi' => 'Mi Browser',
        'webview' => 'Ứng dụng khác (WebView)',
        'other' => 'Khác',
    ];

    public const IN_APP = ['zalo', 'facebook', 'messenger', 'instagram', 'tiktok', 'line', 'wechat', 'google', 'webview'];

    public const DEVICE_TYPES = [
        'mobile' => 'Điện thoại',
        'tablet' => 'Máy tính bảng',
        'desktop' => 'Máy tính',
    ];

    public const OPERATING_SYSTEMS = [
        'ios' => 'iOS',
        'android' => 'Android',
        'windows' => 'Windows',
        'macos' => 'macOS',
        'other' => 'Khác',
    ];

    public const BRANDS = [
        'apple' => 'Apple',
        'samsung' => 'Samsung',
        'xiaomi' => 'Xiaomi',
        'oppo' => 'OPPO',
        'vivo' => 'vivo',
        'realme' => 'realme',
        'huawei' => 'Huawei / Honor',
        'google' => 'Google Pixel',
        'nokia' => 'Nokia',
        'other' => 'Hãng khác / ẩn',
    ];

    private const APP_RULES = [
        'zalo' => '/zalo/i',
        'messenger' => '/FBAN\/Messenger|FB_IAB\/MESSENGER|MessengerForiOS|Orca-Android|MessengerLite/i',
        'instagram' => '/Instagram/i',
        'facebook' => '/FBAN|FBAV|FB_IAB|FBIOS|FB4A|\[FB/i',
        'tiktok' => '/musical_ly|BytedanceWebview|TikTok|trill_|ByteLocale/i',
        'line' => '/\sLine\//i',
        'wechat' => '/MicroMessenger/i',
        'google' => '/\bGSA\/|GoogleApp/i',
        'coccoc' => '/coc_coc_browser|CocCoc/i',
        'samsung' => '/SamsungBrowser/i',
        'xiaomi' => '/MiuiBrowser|XiaoMi\/MiuiBrowser/i',
        'edge' => '/EdgiOS|EdgA\/|Edg\//i',
        'opera' => '/OPR\/|OPiOS|OPT\/|Opera/i',
        'firefox' => '/FxiOS|Firefox\//i',
        'webview' => '/;\s?wv\)|WebView/i',
        'chrome' => '/CriOS|Chrome\//i',
        'safari' => '/Safari\//i',
    ];

    private const BRAND_RULES = [
        'samsung' => '/SM-[A-Z0-9]+|SAMSUNG|Galaxy/i',
        'xiaomi' => '/Redmi|Xiaomi|\bMI\s|POCO|\bM2\d{3}|\b2\d{3}[0-9A-Z]{5,}\b/i',
        'oppo' => '/OPPO|\bCPH\d{4}/i',
        'realme' => '/realme|\bRMX\d{4}/i',
        'vivo' => '/\bvivo\b|\bV\d{4}[A-Z]?\b/i',
        'huawei' => '/HUAWEI|HONOR|\b(?:ELE|VOG|ANA|NOH|LIO|JNY)-/i',
        'google' => '/Pixel\s/i',
        'nokia' => '/Nokia/i',
    ];

    /**
     * @return array{scan_app: string, device_type: string, os: string, os_version: ?string, device_brand: string}
     */
    public static function parse(?string $userAgent): array
    {
        $ua = (string) $userAgent;
        [$os, $version] = self::os($ua);

        return [
            'scan_app' => self::firstMatch($ua, self::APP_RULES, 'other'),
            'device_type' => self::deviceType($ua, $os),
            'os' => $os,
            'os_version' => $version,
            'device_brand' => self::brand($ua, $os),
        ];
    }

    public static function appLabel(?string $key): string
    {
        return __(self::APPS[$key ?? 'other'] ?? self::APPS['other']);
    }

    public static function isInApp(?string $key): bool
    {
        return in_array($key, self::IN_APP, true);
    }

    private static function os(string $ua): array
    {
        if (preg_match('/(?:iPhone|iPad|iPod).*?OS (\d+)[_.]?(\d+)?/i', $ua, $m)) {
            return ['ios', $m[1]];
        }

        if (preg_match('/Android\s?(\d+)/i', $ua, $m)) {
            return ['android', preg_match('/Android 10; K\)/', $ua) ? null : $m[1]];
        }

        if (preg_match('/Windows NT/i', $ua)) {
            return ['windows', null];
        }

        if (preg_match('/Macintosh|Mac OS X/i', $ua)) {
            return ['macos', null];
        }

        return ['other', null];
    }

    private static function deviceType(string $ua, string $os): string
    {
        if (preg_match('/iPad|Tablet/i', $ua) || ($os === 'android' && ! preg_match('/Mobile/i', $ua))) {
            return 'tablet';
        }

        return in_array($os, ['ios', 'android'], true) || preg_match('/Mobi/i', $ua) ? 'mobile' : 'desktop';
    }

    private static function brand(string $ua, string $os): string
    {
        if (in_array($os, ['ios', 'macos'], true)) {
            return 'apple';
        }

        return $os === 'android' ? self::firstMatch($ua, self::BRAND_RULES, 'other') : 'other';
    }

    private static function firstMatch(string $ua, array $rules, string $fallback): string
    {
        foreach ($rules as $key => $pattern) {
            if (preg_match($pattern, $ua)) {
                return $key;
            }
        }

        return $fallback;
    }
}
