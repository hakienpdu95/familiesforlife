<?php

namespace App\Shared\Traceability\Actions;

use App\Models\SystemSetting;
use App\Shared\Traceability\Enums\QrScanSource;
use App\Shared\Traceability\Models\QrIdentity;
use App\Shared\Traceability\Models\QrScan;
use App\Shared\Traceability\Support\QrScanUserAgent;
use Carbon\CarbonImmutable;
use Carbon\CarbonPeriod;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Relations\Relation;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Lorisleiva\Actions\Concerns\AsAction;

class BuildQrScanStatsAction
{
    use AsAction;

    public const TOP_LIMIT = 15;

    public const SOURCE_ORDER = ['qr', 'campaign', 'referral', 'internal', 'unknown'];

    private string $timezone;

    private string $appTimezone;

    public function handle(CarbonImmutable $from, CarbonImmutable $to, ?string $source = null, ?int $identityId = null, ?string $app = null): array
    {
        $this->timezone = SystemSetting::current()->timezone ?: config('app.timezone');
        $this->appTimezone = config('app.timezone');

        $start = $from->setTimezone($this->timezone)->startOfDay();
        $end = $to->setTimezone($this->timezone)->endOfDay();
        $days = (int) $start->diffInDays($end) + 1;

        $current = fn (): Builder => $this->scope($start, $end, $source, $identityId, $app);
        $previous = fn (): Builder => $this->scope($start->subDays($days), $start->subSecond(), $source, $identityId, $app);
        $local = $this->localTimeSql();

        $totals = $current()->selectRaw('COUNT(*) as scans, SUM(is_repeat = 0) as new_scans, COUNT(DISTINCT visitor_hash) as visitors, COUNT(DISTINCT qr_identity_id) as codes')->toBase()->first();
        $previousTotals = $previous()->selectRaw('COUNT(*) as scans, COUNT(DISTINCT visitor_hash) as visitors')->toBase()->first();
        $returning = DB::query()->fromSub($current()->select('visitor_hash')->groupBy('visitor_hash')->havingRaw('COUNT(*) > 1')->toBase(), 'v')->count();
        $activeCodes = $identityId ? null : QrIdentity::query()->where('status', QrIdentity::STATUS_ACTIVE)->count();

        $sources = $this->breakdown($current(), "COALESCE(scan_source, 'unknown')");
        $trend = $this->trend($current(), $local, $start, $end);

        return [
            'range' => [
                'from' => $start->toIso8601String(),
                'to' => $end->toIso8601String(),
                'days' => $days,
                'previous_from' => $start->subDays($days)->toIso8601String(),
                'previous_to' => $start->subDay()->toIso8601String(),
            ],
            'summary' => [
                'scans' => (int) $totals->scans,
                'scans_previous' => (int) $previousTotals->scans,
                'scans_change' => $this->change((int) $totals->scans, (int) $previousTotals->scans),
                'new_scans' => (int) $totals->new_scans,
                'repeat_scans' => (int) $totals->scans - (int) $totals->new_scans,
                'visitors' => (int) $totals->visitors,
                'visitors_change' => $this->change((int) $totals->visitors, (int) $previousTotals->visitors),
                'returning_rate' => $totals->visitors ? round($returning / $totals->visitors * 100, 1) : null,
                'scans_per_visitor' => $totals->visitors ? round($totals->scans / $totals->visitors, 2) : null,
                'codes' => (int) $totals->codes,
                'active_codes' => $activeCodes,
                'coverage' => $activeCodes ? round($totals->codes / $activeCodes * 100, 1) : null,
                'per_day' => round($totals->scans / max($days, 1), 1),
                'peak' => $trend['peak'],
                'direct_share' => $totals->scans ? round(($sources['qr'] ?? 0) / $totals->scans * 100, 1) : null,
            ],
            'trend' => $trend,
            'timing' => $this->timing($current(), $local),
            'sources' => $this->labelled($sources, fn ($key) => $this->sourceLabel($key), self::SOURCE_ORDER),
            'apps' => $this->apps($current(), (int) $totals->scans),
            'audience' => [
                'devices' => $this->labelled($this->breakdown($current(), "COALESCE(device_type, 'unknown')"), fn ($key) => __(QrScanUserAgent::DEVICE_TYPES[$key] ?? 'Chưa phân loại'), array_keys(QrScanUserAgent::DEVICE_TYPES)),
                'os' => $this->operatingSystems($current()),
                'brands' => $this->labelled($this->breakdown($current(), "COALESCE(device_brand, 'unknown')"), fn ($key) => __(QrScanUserAgent::BRANDS[$key] ?? 'Chưa phân loại')),
                'locales' => $this->labelled($this->breakdown($current(), "COALESCE(locale, 'unknown')"), fn ($key) => $key === 'unknown' ? __('Chưa ghi nhận') : strtoupper($key)),
            ],
            'kinds' => $identityId ? [] : $this->kinds($current()),
            'top' => $identityId ? [] : $this->top($current(), (int) $totals->scans),
            'generated_at' => CarbonImmutable::now()->toIso8601String(),
        ];
    }

    public function sourceLabel(string $key): string
    {
        return QrScanSource::tryFrom($key)?->label() ?? __('Chưa phân loại');
    }

    private function scope(CarbonImmutable $from, CarbonImmutable $to, ?string $source, ?int $identityId, ?string $app): Builder
    {
        return QrScan::query()
            ->whereBetween('scanned_at', [$from->setTimezone($this->appTimezone), $to->setTimezone($this->appTimezone)])
            ->when($source, fn ($q) => $q->where('scan_source', $source))
            ->when($identityId, fn ($q) => $q->where('qr_identity_id', $identityId))
            ->when($app, fn ($q) => $q->where('scan_app', $app));
    }

    private function apps(Builder $query, int $scans): array
    {
        $rows = $query->selectRaw("COALESCE(scan_app, 'unknown') as app, COALESCE(os, 'other') as os_key, COUNT(*) as total, COUNT(DISTINCT visitor_hash) as visitors")
            ->groupBy('app', 'os_key')
            ->toBase()
            ->get();

        return $rows->groupBy('app')
            ->map(function (Collection $items, string $app) use ($scans) {
                $total = (int) $items->sum('total');
                $byOs = $items->pluck('total', 'os_key');

                return [
                    'key' => $app,
                    'label' => $app === 'unknown' ? __('Chưa phân loại') : QrScanUserAgent::appLabel($app),
                    'in_app' => QrScanUserAgent::isInApp($app),
                    'total' => $total,
                    'visitors' => (int) $items->sum('visitors'),
                    'percent' => $scans ? round($total / $scans * 100, 1) : 0,
                    'ios' => (int) ($byOs['ios'] ?? 0),
                    'android' => (int) ($byOs['android'] ?? 0),
                    'other' => $total - (int) ($byOs['ios'] ?? 0) - (int) ($byOs['android'] ?? 0),
                ];
            })
            ->sortByDesc('total')
            ->values()
            ->all();
    }

    private function operatingSystems(Builder $query): array
    {
        $rows = $query->selectRaw("COALESCE(os, 'unknown') as os_key, os_version, COUNT(*) as total")
            ->groupBy('os_key', 'os_version')
            ->toBase()
            ->get();

        $sum = $rows->sum('total') ?: 1;

        return $rows->groupBy('os_key')
            ->map(fn (Collection $items, string $os) => [
                'key' => $os,
                'label' => __(QrScanUserAgent::OPERATING_SYSTEMS[$os] ?? 'Chưa phân loại'),
                'total' => (int) $items->sum('total'),
                'percent' => round($items->sum('total') / $sum * 100, 1),
                'versions' => $items->whereNotNull('os_version')
                    ->sortByDesc('total')
                    ->take(4)
                    ->map(fn ($row) => ['version' => $row->os_version, 'total' => (int) $row->total])
                    ->values()
                    ->all(),
            ])
            ->sortByDesc('total')
            ->values()
            ->all();
    }

    private function localTimeSql(): string
    {
        $now = CarbonImmutable::now();
        $offset = $now->setTimezone($this->timezone)->getOffset() - $now->setTimezone($this->appTimezone)->getOffset();

        return $offset === 0 ? 'scanned_at' : "DATE_ADD(scanned_at, INTERVAL {$offset} SECOND)";
    }

    private function breakdown(Builder $query, string $expression): array
    {
        return $query->selectRaw("{$expression} as bucket, COUNT(*) as total")
            ->groupBy('bucket')
            ->orderByDesc('total')
            ->toBase()
            ->pluck('total', 'bucket')
            ->map(fn ($total) => (int) $total)
            ->all();
    }

    private function labelled(array $counts, callable $label, array $order = []): array
    {
        $sum = array_sum($counts) ?: 1;
        $rank = array_flip($order);

        return collect($counts)
            ->map(fn ($total, $key) => [
                'key' => (string) $key,
                'label' => $label((string) $key),
                'total' => $total,
                'percent' => round($total / $sum * 100, 1),
            ])
            ->sortBy(fn ($row) => [$order ? ($rank[$row['key']] ?? PHP_INT_MAX) : 0, -$row['total']])
            ->values()
            ->all();
    }

    private function trend(Builder $query, string $local, CarbonImmutable $start, CarbonImmutable $end): array
    {
        $rows = (clone $query)
            ->selectRaw("DATE({$local}) as day, COALESCE(scan_source, 'unknown') as bucket, COUNT(*) as total")
            ->groupBy('day', 'bucket')
            ->toBase()
            ->get();

        $visitors = (clone $query)
            ->selectRaw("DATE({$local}) as day, COUNT(DISTINCT visitor_hash) as total")
            ->groupBy('day')
            ->toBase()
            ->pluck('total', 'day');

        $dates = collect(CarbonPeriod::create($start->startOfDay(), '1 day', $end->startOfDay()))->map(fn ($d) => $d->format('Y-m-d'));
        $buckets = collect(self::SOURCE_ORDER)->intersect($rows->pluck('bucket')->unique())->values();
        $matrix = $rows->groupBy('bucket')->map(fn (Collection $items) => $items->pluck('total', 'day'));

        $totals = $dates->map(fn ($day) => (int) $rows->where('day', $day)->sum('total'));
        $peakIndex = $totals->max() > 0 ? $totals->search($totals->max()) : null;

        return [
            'dates' => $dates->values()->all(),
            'series' => $buckets->map(fn ($bucket) => [
                'key' => $bucket,
                'label' => $this->sourceLabel($bucket),
                'data' => $dates->map(fn ($day) => (int) ($matrix[$bucket][$day] ?? 0))->values()->all(),
            ])->all(),
            'visitors' => $dates->map(fn ($day) => (int) ($visitors[$day] ?? 0))->values()->all(),
            'peak' => $peakIndex === null ? null : ['date' => CarbonImmutable::parse($dates[$peakIndex], $this->timezone)->toIso8601String(), 'scans' => $totals[$peakIndex]],
        ];
    }

    private function timing(Builder $query, string $local): array
    {
        $rows = $query->selectRaw("WEEKDAY({$local}) as weekday, HOUR({$local}) as hour, COUNT(*) as total")
            ->groupBy('weekday', 'hour')
            ->toBase()
            ->get();

        $byHour = array_fill(0, 24, 0);
        $byWeekday = array_fill(0, 7, 0);

        foreach ($rows as $row) {
            $byHour[(int) $row->hour] += (int) $row->total;
            $byWeekday[(int) $row->weekday] += (int) $row->total;
        }

        return [
            'hours' => $byHour,
            'weekdays' => $byWeekday,
            'peak_hour' => max($byHour) > 0 ? array_search(max($byHour), $byHour, true) : null,
            'peak_weekday' => max($byWeekday) > 0 ? array_search(max($byWeekday), $byWeekday, true) : null,
        ];
    }

    private function kinds(Builder $query): array
    {
        $counts = $query->join('qr_identities', 'qr_identities.id', '=', 'qr_scans.qr_identity_id')
            ->selectRaw('qr_identities.target_type as bucket, COUNT(*) as total, COUNT(DISTINCT qr_scans.qr_identity_id) as codes')
            ->groupBy('bucket')
            ->orderByDesc('total')
            ->toBase()
            ->get();

        $sum = $counts->sum('total') ?: 1;

        return $counts->map(function ($row) use ($sum) {
            $kind = class_basename((string) (Relation::getMorphedModel($row->bucket) ?? $row->bucket));

            return [
                'key' => $kind,
                'label' => isset(QrIdentity::KIND_LABELS[$kind]) ? __(QrIdentity::KIND_LABELS[$kind]) : $kind,
                'total' => (int) $row->total,
                'codes' => (int) $row->codes,
                'percent' => round($row->total / $sum * 100, 1),
            ];
        })->values()->all();
    }

    private function top(Builder $query, int $scans): array
    {
        $rows = $query->selectRaw('qr_identity_id, COUNT(*) as scans, COUNT(DISTINCT visitor_hash) as visitors, SUM(scan_source = ?) as direct, MAX(scanned_at) as last_scanned_at', [QrScanSource::Qr->value])
            ->groupBy('qr_identity_id')
            ->orderByDesc('scans')
            ->limit(self::TOP_LIMIT)
            ->toBase()
            ->get();

        $identities = QrIdentity::query()
            ->with(QrIdentity::targetEagerLoad())
            ->whereIn('id', $rows->pluck('qr_identity_id'))
            ->get()
            ->keyBy('id');

        return $rows->map(function ($row) use ($identities, $scans) {
            $identity = $identities->get($row->qr_identity_id);

            return [
                'qr_code' => $identity?->qr_code,
                'url' => $identity?->publicUrl(),
                'kind' => $identity?->targetKindLabel(),
                'title' => $identity?->targetTitle() ?: __('(đã xoá)'),
                'trashed' => (bool) $identity?->targetTrashed(),
                'revoked' => $identity && ! $identity->isActive(),
                'scans' => (int) $row->scans,
                'share' => $scans ? round($row->scans / $scans * 100, 1) : 0,
                'visitors' => (int) $row->visitors,
                'direct_share' => $row->scans ? round($row->direct / $row->scans * 100) : 0,
                'last_scanned_at' => format_datetime(CarbonImmutable::parse($row->last_scanned_at, $this->appTimezone)),
            ];
        })->all();
    }

    private function change(int $current, int $previous): ?float
    {
        return $previous === 0 ? null : round(($current - $previous) / $previous * 100, 1);
    }
}
