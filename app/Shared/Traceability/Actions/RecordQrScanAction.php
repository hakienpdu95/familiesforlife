<?php

namespace App\Shared\Traceability\Actions;

use App\Shared\Traceability\Enums\QrScanSource;
use App\Shared\Traceability\Models\QrReport;
use App\Shared\Traceability\Models\QrScan;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Lorisleiva\Actions\Concerns\AsAction;

class RecordQrScanAction
{
    use AsAction;

    private const BOT_PATTERN = '/bot|crawl|spider|slurp|preview|facebookexternalhit|curl|wget|python|headless/i';

    /**
     * @return array{scan_id: ?string, scan_source: string, locale: string}
     */
    public function handle(Request $request, int $identityId): array
    {
        $source = QrScanSource::fromRequest($request);
        $locale = app()->getLocale();
        $context = ['scan_id' => null, 'scan_source' => $source->value, 'locale' => $locale];

        $userAgent = (string) $request->userAgent();

        if ($userAgent === '' || preg_match(self::BOT_PATTERN, $userAgent) || $request->user()?->isPlatform()) {
            return $context;
        }

        $hash = QrReport::visitorHash($request->ip());

        $recent = QrScan::query()
            ->where('qr_identity_id', $identityId)
            ->where('visitor_hash', $hash)
            ->where('scanned_at', '>=', now()->subMinutes(QrScan::DEDUPE_MINUTES))
            ->latest('scanned_at')
            ->first(['id', 'uuid', 'scan_source']);

        if ($recent) {
            if (! $recent->uuid) {
                $recent->forceFill(['uuid' => (string) Str::uuid()])->saveQuietly();
            }

            return [...$context, 'scan_id' => $recent->uuid, 'scan_source' => $recent->scan_source?->value ?? $source->value];
        }

        $uuid = (string) Str::uuid();

        dispatch(fn () => QrScan::create([
            'uuid' => $uuid,
            'qr_identity_id' => $identityId,
            'visitor_hash' => $hash,
            'user_agent' => mb_substr($userAgent, 0, 255),
            'scan_source' => $source->value,
            'locale' => $locale,
            'scanned_at' => now(),
        ]))->afterResponse();

        return [...$context, 'scan_id' => $uuid];
    }
}
