<?php

namespace App\Http\Controllers\Backend;

use App\Http\Controllers\Controller;
use App\Models\SystemSetting;
use App\Shared\Traceability\Actions\BuildQrScanStatsAction;
use App\Shared\Traceability\Enums\QrScanSource;
use App\Shared\Traceability\Models\QrIdentity;
use App\Shared\Traceability\Models\QrScan;
use Carbon\CarbonImmutable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Validation\Rule;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\StreamedResponse;

class QrScanStatsController extends Controller
{
    public const PRESETS = [7, 30, 90];

    public const CACHE_SECONDS = 300;

    private const MAX_RANGE_DAYS = 366;

    public function index(Request $request, BuildQrScanStatsAction $stats): View
    {
        $this->authorize('viewQrScanStats');

        $filters = $this->filters($request);
        $identity = $filters['code'] ? QrIdentity::query()->with(QrIdentity::targetEagerLoad())->where('qr_code', $filters['code'])->first() : null;
        $codeNotFound = $filters['code'] && ! $identity;

        $cacheKey = 'qr-scan-stats:v2:'.md5(json_encode([
            $filters['from']->toDateString(), $filters['to']->toDateString(), $filters['source'], $identity?->id,
            app()->getLocale(), SystemSetting::current()->timezone,
        ]));

        if ($request->boolean('refresh')) {
            Cache::forget($cacheKey);
        }

        return view('backend.qr-scans.index', [
            'filters' => $filters,
            'query' => $this->query($filters),
            'identity' => $identity,
            'codeNotFound' => $codeNotFound,
            'stats' => $codeNotFound ? null : Cache::remember($cacheKey, self::CACHE_SECONDS, fn () => $stats->handle($filters['from'], $filters['to'], $filters['source'], $identity?->id)),
            'sources' => collect(QrScanSource::cases())->mapWithKeys(fn (QrScanSource $s) => [$s->value => $s->label()])->all(),
            'presets' => self::PRESETS,
        ]);
    }

    public function history(Request $request): JsonResponse
    {
        $this->authorize('viewQrScanStats');

        $filters = $this->filters($request);
        $validated = $request->validate([
            'page' => ['nullable', 'integer', 'min:1'],
            'size' => ['nullable', 'integer', 'min:10', 'max:100'],
        ]);

        $paginator = $this->historyQuery($filters)
            ->with(['identity' => fn ($q) => $q->with(QrIdentity::targetEagerLoad())])
            ->orderByDesc('scanned_at')
            ->orderByDesc('id')
            ->paginate(min(100, max(10, (int) ($validated['size'] ?? 25))), ['*'], 'page', max(1, (int) ($validated['page'] ?? 1)));

        return response()->json([
            'data' => collect($paginator->items())->map(fn (QrScan $scan) => $this->row($scan))->values(),
            'last_page' => $paginator->lastPage(),
            'total' => $paginator->total(),
        ]);
    }

    public function export(Request $request): StreamedResponse
    {
        $this->authorize('viewQrScanStats');

        $filters = $this->filters($request);
        $filename = 'qr-scans_'.$filters['from']->format('Ymd').'-'.$filters['to']->format('Ymd').($filters['code'] ? '_'.$filters['code'] : '').'.csv';

        return response()->streamDownload(function () use ($filters) {
            $out = fopen('php://output', 'w');
            fwrite($out, "\xEF\xBB\xBF");
            fputcsv($out, [__('Thời gian'), __('Mã QR'), __('Loại đối tượng'), __('Đối tượng'), __('Nguồn'), __('Ngôn ngữ'), __('Thiết bị'), __('Hệ điều hành'), __('Mã khách')], escape: '');

            $this->historyQuery($filters)
                ->with(['identity' => fn ($q) => $q->with(QrIdentity::targetEagerLoad())])
                ->lazyById(1000)
                ->each(function (QrScan $scan) use ($out) {
                    $row = $this->row($scan);
                    fputcsv($out, [$row['scanned_at'], $row['qr_code'], $row['kind'], $row['title'], $row['source_label'], $row['locale'], $row['device'], $row['platform'], $row['visitor']], escape: '');
                });

            fclose($out);
        }, $filename, ['Content-Type' => 'text/csv; charset=UTF-8']);
    }

    private function historyQuery(array $filters): Builder
    {
        $appTimezone = config('app.timezone');

        return QrScan::query()
            ->whereBetween('scanned_at', [$filters['from']->startOfDay()->setTimezone($appTimezone), $filters['to']->endOfDay()->setTimezone($appTimezone)])
            ->when($filters['source'], fn ($q, $source) => $q->where('scan_source', $source))
            ->when($filters['code'], fn ($q, $code) => $q->whereHas('identity', fn ($i) => $i->where('qr_code', $code)));
    }

    private function query(array $filters): array
    {
        return array_filter([
            'days' => $filters['days'],
            'from' => $filters['days'] ? null : $filters['from']->format('Y-m-d'),
            'to' => $filters['days'] ? null : $filters['to']->format('Y-m-d'),
            'source' => $filters['source'],
            'code' => $filters['code'],
        ]);
    }

    private function row(QrScan $scan): array
    {
        $identity = $scan->identity;
        $device = QrScan::deviceOf($scan->user_agent);
        $platform = QrScan::platformOf($scan->user_agent);

        return [
            'scanned_at' => format_datetime($scan->scanned_at, true),
            'qr_code' => $identity?->qr_code,
            'qr_url' => $identity?->publicUrl(),
            'kind' => $identity?->targetKindLabel(),
            'title' => $identity?->targetTitle() ?: __('(đã xoá)'),
            'source' => $scan->scan_source?->value,
            'source_label' => $scan->scan_source?->label() ?? __('Chưa phân loại'),
            'locale' => $scan->locale ? strtoupper($scan->locale) : null,
            'device' => __(QrScan::DEVICES[$device]),
            'platform' => __(QrScan::PLATFORMS[$platform]),
            'visitor' => substr((string) $scan->visitor_hash, 0, 8),
        ];
    }

    /**
     * @return array{from: CarbonImmutable, to: CarbonImmutable, days: ?int, source: ?string, code: ?string}
     */
    private function filters(Request $request): array
    {
        $validated = $request->validate([
            'days' => ['nullable', 'integer', Rule::in(self::PRESETS)],
            'from' => ['nullable', 'date_format:Y-m-d'],
            'to' => ['nullable', 'date_format:Y-m-d'],
            'source' => ['nullable', Rule::enum(QrScanSource::class)],
            'code' => ['nullable', 'string', 'max:'.QrIdentity::CODE_LENGTH * 2],
        ]);

        $timezone = SystemSetting::current()->timezone ?: config('app.timezone');
        $today = CarbonImmutable::now($timezone)->startOfDay();
        $custom = filled($validated['from'] ?? null) && filled($validated['to'] ?? null);

        if ($custom) {
            $from = CarbonImmutable::createFromFormat('Y-m-d', $validated['from'], $timezone)->startOfDay();
            $to = CarbonImmutable::createFromFormat('Y-m-d', $validated['to'], $timezone)->startOfDay();
            [$from, $to] = $from->greaterThan($to) ? [$to, $from] : [$from, $to];
            $from = $from->max($to->subDays(self::MAX_RANGE_DAYS - 1));
            $to = $to->min($today);
            $days = null;
        } else {
            $days = (int) ($validated['days'] ?? 30);
            $to = $today;
            $from = $today->subDays($days - 1);
        }

        return [
            'from' => $from,
            'to' => $to,
            'days' => $days,
            'source' => $validated['source'] ?? null,
            'code' => filled($validated['code'] ?? null) ? trim($validated['code']) : null,
        ];
    }
}
