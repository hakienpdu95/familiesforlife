<?php

namespace App\Shared\Traceability\Actions;

use App\Shared\Traceability\Enums\QrScanSource;
use App\Shared\Traceability\Models\QrScan;
use App\Shared\Traceability\Support\QrScanUserAgent;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cookie;
use Illuminate\Support\Str;
use Lorisleiva\Actions\Concerns\AsAction;

class RecordQrScanAction
{
    use AsAction;

    public const VISITOR_COOKIE = 'qr_vid';

    private const VISITOR_COOKIE_MINUTES = 60 * 24 * 395;

    private const BOT_PATTERN = '/bot|crawl|spider|slurp|preview|facebookexternalhit|facebookcatalog|whatsapp|telegram|skype|curl|wget|python|httpclient|okhttp|headless|lighthouse|pagespeed|gtmetrix|pingdom|uptime|monitor/i';

    /**
     * @return array{scan_id: ?string, scan_source: string, locale: string, scan_app: string, is_repeat: bool}
     */
    public function handle(Request $request, int $identityId): array
    {
        $source = QrScanSource::fromRequest($request);
        $locale = app()->getLocale();
        $userAgent = (string) $request->userAgent();
        $device = QrScanUserAgent::parse($userAgent);
        $context = ['scan_id' => null, 'scan_source' => $source->value, 'locale' => $locale, 'scan_app' => $device['scan_app'], 'is_repeat' => false];

        if (! $this->countable($request, $userAgent)) {
            return $context;
        }

        $hasCookie = Str::isUuid((string) $request->cookie(self::VISITOR_COOKIE));
        $visitorHash = $this->hash($this->visitorId($request));
        $clientHash = $this->hash($request->ip().'|'.$userAgent);

        $recent = QrScan::query()
            ->where('qr_identity_id', $identityId)
            ->where('scanned_at', '>=', now()->subMinutes(QrScan::DEDUPE_MINUTES))
            ->where(fn ($q) => $q->where('visitor_hash', $visitorHash)->orWhere('client_hash', $clientHash))
            ->latest('scanned_at')
            ->first(['visitor_hash']);

        if ($recent && ! $hasCookie) {
            $visitorHash = $recent->visitor_hash;
        }

        $isRepeat = $recent !== null;

        $uuid = (string) Str::uuid();

        dispatch(fn () => QrScan::create([
            'uuid' => $uuid,
            'qr_identity_id' => $identityId,
            'visitor_hash' => $visitorHash,
            'client_hash' => $clientHash,
            'user_agent' => mb_substr($userAgent, 0, 255),
            'scan_source' => $source->value,
            'locale' => $locale,
            ...$device,
            'is_repeat' => $isRepeat,
            'scanned_at' => now(),
        ]))->afterResponse();

        return [...$context, 'scan_id' => $uuid, 'is_repeat' => $isRepeat];
    }

    private function countable(Request $request, string $userAgent): bool
    {
        if ($userAgent === '' || preg_match(self::BOT_PATTERN, $userAgent) || ! $request->isMethod('GET')) {
            return false;
        }

        $purpose = strtolower((string) ($request->header('Sec-Purpose') ?? $request->header('Purpose') ?? $request->header('X-Moz')));

        if (str_contains($purpose, 'prefetch') || str_contains($purpose, 'prerender')) {
            return false;
        }

        return ! $request->user()?->isPlatform();
    }

    private function visitorId(Request $request): string
    {
        $id = (string) $request->cookie(self::VISITOR_COOKIE);

        if (! Str::isUuid($id)) {
            $id = (string) Str::uuid();
        }

        Cookie::queue(Cookie::make(self::VISITOR_COOKIE, $id, self::VISITOR_COOKIE_MINUTES, '/', null, $request->isSecure(), true, false, 'lax'));

        return $id;
    }

    private function hash(string $value): string
    {
        return hash_hmac('sha256', $value, (string) config('app.key'));
    }
}
