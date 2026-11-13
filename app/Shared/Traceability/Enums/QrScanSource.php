<?php

namespace App\Shared\Traceability\Enums;

use Illuminate\Http\Request;

enum QrScanSource: string
{
    case Qr = 'qr';
    case Campaign = 'campaign';
    case Internal = 'internal';
    case Referral = 'referral';

    public static function fromRequest(Request $request): self
    {
        if ($request->filled('utm_source')) {
            return self::Campaign;
        }

        $referer = (string) $request->headers->get('referer');
        $host = $referer !== '' ? strtolower((string) parse_url($referer, PHP_URL_HOST)) : '';

        if ($host === '') {
            return self::Qr;
        }

        return $host === strtolower($request->getHost()) ? self::Internal : self::Referral;
    }

    public function label(): string
    {
        return match ($this) {
            self::Qr => __('Quét mã QR'),
            self::Campaign => __('Chiến dịch (UTM)'),
            self::Internal => __('Từ trang trong hệ thống'),
            self::Referral => __('Từ website khác'),
        };
    }
}
