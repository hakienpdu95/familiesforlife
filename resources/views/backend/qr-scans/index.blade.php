@extends('layouts.backend')
@section('title', __('Thống kê quét mã QR'))

@php
    $sourceColors = ['qr' => '#2563eb', 'campaign' => '#9333ea', 'referral' => '#0d9488', 'internal' => '#a8a29e', 'unknown' => '#d6d3d1'];
    $palette = ['#2563eb', '#0d9488', '#f59e0b', '#9333ea', '#a8a29e', '#e11d48'];
    $sourceBadges = ['qr' => 'badge-primary', 'campaign' => 'badge-secondary', 'internal' => 'badge-ghost', 'referral' => 'badge-accent'];
    $weekdays = [__('Thứ 2'), __('Thứ 3'), __('Thứ 4'), __('Thứ 5'), __('Thứ 6'), __('Thứ 7'), __('Chủ nhật')];
    $delta = function (?float $pct): array {
        if ($pct === null) {
            return ['label' => __('Mới'), 'class' => 'badge-ghost'];
        }

        return ['label' => ($pct > 0 ? '▲ ' : ($pct < 0 ? '▼ ' : '')).number_format(abs($pct), 1).'%', 'class' => $pct > 0 ? 'badge-success badge-soft' : ($pct < 0 ? 'badge-error badge-soft' : 'badge-ghost')];
    };
    $hasData = $stats && $stats['summary']['scans'] > 0;
    $historyParams = ['from' => $filters['from']->format('Y-m-d'), 'to' => $filters['to']->format('Y-m-d'), 'source' => $filters['source'], 'code' => $identity?->qr_code];
@endphp

@section('content')
<div class="space-y-4">

    <div class="flex flex-wrap items-start justify-between gap-3">
        <div class="min-w-0">
            <div class="flex items-center gap-2 text-xs text-base-content/50 mb-1">
                <span>{{ __('Dữ liệu chuyên ngành') }}</span><span>/</span><span>{{ __('Truy xuất QR') }}</span>
            </div>
            <h1 class="text-2xl font-bold text-base-content">{{ __('Thống kê quét mã QR') }}</h1>
            <p class="text-sm text-base-content/55 mt-0.5 max-w-3xl">{{ __('Ghi nhận tại máy chủ, không bị trình chặn quảng cáo ảnh hưởng. Một khách quét lại cùng mã trong :minutes phút chỉ tính 1 lượt.', ['minutes' => \App\Shared\Traceability\Models\QrScan::DEDUPE_MINUTES]) }}</p>
        </div>
        <div class="flex items-center gap-2 shrink-0">
            @if($hasData)
            <a href="{{ route('backend.qr-scans.export', $query) }}" class="btn btn-sm btn-outline gap-1.5">
                <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v2a2 2 0 002 2h12a2 2 0 002-2v-2M7 10l5 5 5-5M12 15V3"/></svg>
                {{ __('Xuất CSV') }}
            </a>
            @endif
            @can('manageQrReports')
            <a href="{{ route('backend.qr-reports.index') }}" class="btn btn-sm btn-ghost">{{ __('Phản ánh mã QR') }}</a>
            @endcan
        </div>
    </div>

    <form method="GET" action="{{ route('backend.qr-scans.index') }}" class="card bg-base-100 border border-base-200" x-data="{ custom: {{ $filters['days'] ? 'false' : 'true' }} }">
        <div class="card-body p-3 gap-2.5">
            <div class="flex flex-wrap items-end gap-2.5">
                <div class="join">
                    @foreach($presets as $preset)
                    <a href="{{ route('backend.qr-scans.index', array_filter(['days' => $preset, 'source' => $filters['source'], 'app' => $filters['app'], 'code' => $filters['code']])) }}"
                       @class(['btn btn-sm join-item', 'btn-primary' => $filters['days'] === $preset])>{{ __(':days ngày', ['days' => $preset]) }}</a>
                    @endforeach
                    <button type="button" @click="custom = !custom" @class(['btn btn-sm join-item', 'btn-primary' => ! $filters['days']])>{{ __('Tuỳ chọn') }}</button>
                </div>
                <div class="flex items-end gap-2" x-show="custom" x-cloak>
                    <label class="flex flex-col">
                        <span class="label-text text-xs mb-1">{{ __('Từ ngày') }}</span>
                        <input type="date" name="from" value="{{ $filters['from']->format('Y-m-d') }}" max="{{ now()->format('Y-m-d') }}" class="input input-sm input-bordered" :disabled="!custom">
                    </label>
                    <label class="flex flex-col">
                        <span class="label-text text-xs mb-1">{{ __('Đến ngày') }}</span>
                        <input type="date" name="to" value="{{ $filters['to']->format('Y-m-d') }}" max="{{ now()->format('Y-m-d') }}" class="input input-sm input-bordered" :disabled="!custom">
                    </label>
                </div>
                @if($filters['days'])<input type="hidden" name="days" value="{{ $filters['days'] }}" :disabled="custom">@endif
                <label class="flex flex-col">
                    <span class="label-text text-xs mb-1">{{ __('Nguồn truy cập') }}</span>
                    <select name="source" class="select select-sm select-bordered min-w-44">
                        <option value="">{{ __('Tất cả nguồn') }}</option>
                        @foreach($sources as $value => $label)
                        <option value="{{ $value }}" @selected($filters['source'] === $value)>{{ $label }}</option>
                        @endforeach
                    </select>
                </label>
                <label class="flex flex-col">
                    <span class="label-text text-xs mb-1">{{ __('Ứng dụng quét') }}</span>
                    <select name="app" class="select select-sm select-bordered min-w-40">
                        <option value="">{{ __('Tất cả ứng dụng') }}</option>
                        @foreach($apps as $value => $label)
                        <option value="{{ $value }}" @selected($filters['app'] === $value)>{{ $label }}</option>
                        @endforeach
                    </select>
                </label>
                <label class="flex flex-col">
                    <span class="label-text text-xs mb-1">{{ __('Mã QR') }}</span>
                    <input type="text" name="code" value="{{ $filters['code'] }}" maxlength="16" spellcheck="false" autocomplete="off" placeholder="{{ __('Tất cả mã') }}" class="input input-sm input-bordered font-mono w-36">
                </label>
                <button type="submit" class="btn btn-sm btn-primary">{{ __('Áp dụng') }}</button>
                @if($filters['source'] || $filters['app'] || $filters['code'] || $filters['days'] !== 30)
                <a href="{{ route('backend.qr-scans.index') }}" class="btn btn-sm btn-ghost text-error">{{ __('Đặt lại') }}</a>
                @endif
            </div>
            <div class="flex flex-wrap items-center justify-between gap-2 border-t border-base-200 pt-2.5 text-xs text-base-content/55">
                <span>
                    {{ __('Kỳ này: :from – :to (:days ngày)', ['from' => format_date($filters['from']), 'to' => format_date($filters['to']), 'days' => $stats['range']['days'] ?? ((int) $filters['from']->diffInDays($filters['to']) + 1)]) }}
                    @if($stats)
                    <span class="text-base-content/35">·</span>
                    {{ __('So với :from – :to', ['from' => format_date($stats['range']['previous_from']), 'to' => format_date($stats['range']['previous_to'])]) }}
                    @endif
                </span>
                @if($stats)
                <span class="flex items-center gap-1.5">
                    {{ __('Cập nhật :time', ['time' => format_datetime($stats['generated_at'])]) }}
                    <a href="{{ route('backend.qr-scans.index', [...$query, 'refresh' => 1]) }}" class="link link-primary">{{ __('Làm mới') }}</a>
                </span>
                @endif
            </div>
        </div>
    </form>

    @if($codeNotFound)
    <div role="alert" class="alert alert-warning text-sm">{{ __('Không tìm thấy mã QR :code.', ['code' => $filters['code']]) }}</div>
    @endif

    @if($identity)
    <div class="card bg-base-100 border border-primary/30">
        <div class="card-body p-4 flex-row flex-wrap items-center gap-x-4 gap-y-2">
            <span class="font-mono text-lg font-bold text-primary">{{ $identity->qr_code }}</span>
            <div class="min-w-0 flex-1">
                <p class="text-xs text-base-content/50">{{ $identity->targetKindLabel() }}</p>
                <p class="font-semibold truncate">{{ $identity->targetTitle() ?: __('(đã xoá)') }}</p>
            </div>
            @unless($identity->isActive())<span class="badge badge-error badge-sm">{{ __('Đã thu hồi') }}</span>@endunless
            <a href="{{ $identity->publicUrl() }}" target="_blank" rel="noopener" class="btn btn-sm btn-outline">{{ __('Mở trang truy xuất') }}</a>
            <a href="{{ route('backend.qr-scans.index', array_filter([...$query, 'code' => null])) }}" class="btn btn-sm btn-ghost">{{ __('Xem tất cả mã') }}</a>
        </div>
    </div>
    @endif

    @if($stats)
    @php
        $s = $stats['summary'];
        $audience = [__('Loại thiết bị') => $stats['audience']['devices'], __('Hãng máy') => $stats['audience']['brands'], __('Ngôn ngữ trang') => $stats['audience']['locales']];
        $appMax = max(1, $stats['apps'][0]['total'] ?? 1);
    @endphp
    <div class="grid grid-cols-2 md:grid-cols-3 xl:grid-cols-5 gap-3">
        @php($d = $delta($s['scans_change']))
        <div class="bg-base-100 border border-base-200 rounded-box p-4">
            <div class="flex items-center justify-between gap-2"><p class="text-xs font-medium text-base-content/55">{{ __('Lượt quét') }}</p><span class="badge badge-sm {{ $d['class'] }}" title="{{ __('Kỳ trước: :value', ['value' => number_format($s['scans_previous'])]) }}">{{ $d['label'] }}</span></div>
            <p class="text-3xl font-bold mt-1.5 tabular-nums">{{ number_format($s['scans']) }}</p>
            <p class="text-xs text-base-content/45 mt-1">{{ __(':new lượt mới · :repeat quét lại', ['new' => number_format($s['new_scans']), 'repeat' => number_format($s['repeat_scans'])]) }}</p>
        </div>
        @php($d = $delta($s['visitors_change']))
        <div class="bg-base-100 border border-base-200 rounded-box p-4">
            <div class="flex items-center justify-between gap-2"><p class="text-xs font-medium text-base-content/55">{{ __('Khách quét') }}</p><span class="badge badge-sm {{ $d['class'] }}">{{ $d['label'] }}</span></div>
            <p class="text-3xl font-bold mt-1.5 tabular-nums">{{ number_format($s['visitors']) }}</p>
            <p class="text-xs text-base-content/45 mt-1">{{ $s['scans_per_visitor'] ? __(':value lượt/khách', ['value' => number_format($s['scans_per_visitor'], 2)]) : '—' }}</p>
        </div>
        <div class="bg-base-100 border border-base-200 rounded-box p-4">
            <p class="text-xs font-medium text-base-content/55">{{ __('Khách quay lại') }}</p>
            <p class="text-3xl font-bold mt-1.5 tabular-nums">{{ $s['returning_rate'] === null ? '—' : number_format($s['returning_rate'], 1).'%' }}</p>
            <p class="text-xs text-base-content/45 mt-1">{{ __('Quét từ 2 lần trở lên trong kỳ') }}</p>
        </div>
        <div class="bg-base-100 border border-base-200 rounded-box p-4">
            <p class="text-xs font-medium text-base-content/55">{{ __('Quét trực tiếp') }}</p>
            <p class="text-3xl font-bold mt-1.5 tabular-nums">{{ $s['direct_share'] === null ? '—' : number_format($s['direct_share'], 1).'%' }}</p>
            <p class="text-xs text-base-content/45 mt-1">{{ __('Mở bằng camera, không qua link') }}</p>
        </div>
        <div class="bg-base-100 border border-base-200 rounded-box p-4 col-span-2 md:col-span-1">
            @if($identity)
            <p class="text-xs font-medium text-base-content/55">{{ __('Trung bình mỗi ngày') }}</p>
            <p class="text-3xl font-bold mt-1.5 tabular-nums">{{ number_format($s['per_day'], $s['per_day'] < 1 ? 2 : 1) }}</p>
            <p class="text-xs text-base-content/45 mt-1">{{ $s['peak'] ? __('Cao nhất :count lượt ngày :date', ['count' => $s['peak']['scans'], 'date' => format_date($s['peak']['date'])]) : '—' }}</p>
            @else
            <p class="text-xs font-medium text-base-content/55">{{ __('Độ phủ mã') }}</p>
            <p class="text-3xl font-bold mt-1.5 tabular-nums">{{ number_format($s['codes']) }}<span class="text-base font-medium text-base-content/40"> / {{ number_format($s['active_codes']) }}</span></p>
            <div class="flex items-center gap-2 mt-1.5">
                <progress class="progress progress-primary h-1.5 flex-1" value="{{ $s['coverage'] ?? 0 }}" max="100"></progress>
                <span class="text-xs text-base-content/55 tabular-nums">{{ $s['coverage'] === null ? '—' : $s['coverage'].'%' }}</span>
            </div>
            <p class="text-xs text-base-content/45 mt-1">{{ __('Mã đang hoạt động có lượt quét') }}</p>
            @endif
        </div>
    </div>

    @if(! $hasData)
    <div class="card bg-base-100 border border-dashed border-base-300">
        <div class="card-body items-center text-center py-14">
            <p class="font-semibold">{{ __('Chưa có lượt quét nào trong kỳ này') }}</p>
            <p class="text-sm text-base-content/55">{{ __('Thử mở rộng khoảng thời gian hoặc bỏ bớt bộ lọc.') }}</p>
        </div>
    </div>
    @else
    <div class="grid grid-cols-1 xl:grid-cols-12 gap-4">
        <section class="card bg-base-100 border border-base-200 xl:col-span-8 min-w-0">
            <div class="card-body p-4 gap-3">
                <div class="flex flex-wrap items-start justify-between gap-2">
                    <div>
                        <h2 class="font-semibold">{{ __('Xu hướng lượt quét') }}</h2>
                        <p class="text-xs text-base-content/50">{{ __('Cột: lượt quét theo nguồn · Đường: số khách trong ngày') }}</p>
                    </div>
                    <div class="flex gap-4 text-right">
                        <div><p class="text-[11px] uppercase tracking-wide text-base-content/45">{{ __('TB/ngày') }}</p><p class="font-semibold tabular-nums">{{ number_format($s['per_day'], $s['per_day'] < 1 ? 2 : 1) }}</p></div>
                        @if($s['peak'])
                        <div><p class="text-[11px] uppercase tracking-wide text-base-content/45">{{ __('Ngày cao nhất') }}</p><p class="font-semibold tabular-nums">{{ format_date($s['peak']['date']) }} · {{ number_format($s['peak']['scans']) }}</p></div>
                        @endif
                    </div>
                </div>
                <div id="qr-scan-trend" class="w-full h-72"></div>
            </div>
        </section>

        <section class="card bg-base-100 border border-base-200 xl:col-span-4 min-w-0">
            <div class="card-body p-4 gap-3">
                <h2 class="font-semibold">{{ __('Nguồn truy cập') }}</h2>
                <div id="qr-scan-sources" class="w-full h-44"></div>
                <ul class="space-y-1.5 text-sm">
                    @foreach($stats['sources'] as $row)
                    <li class="flex items-center gap-2">
                        <span class="w-2.5 h-2.5 rounded-full shrink-0" style="background: {{ $sourceColors[$row['key']] ?? $sourceColors['unknown'] }}"></span>
                        <span class="flex-1 truncate">{{ $row['label'] }}</span>
                        <span class="tabular-nums font-medium">{{ number_format($row['total']) }}</span>
                        <span class="tabular-nums text-base-content/50 w-12 text-right">{{ $row['percent'] }}%</span>
                    </li>
                    @endforeach
                </ul>
            </div>
        </section>

        <section class="card bg-base-100 border border-base-200 xl:col-span-8 min-w-0">
            <div class="card-body p-4 gap-3">
                <div class="flex flex-wrap items-start justify-between gap-2">
                    <div class="min-w-0 flex-1">
                        <h2 class="font-semibold">{{ __('Ứng dụng dùng để quét') }}</h2>
                        <p class="text-xs text-base-content/50">{{ __('Camera iPhone mở bằng Safari, camera Android/Google Lens thường mở bằng Chrome; quét trong Zalo, Facebook… mở bằng trình duyệt của chính ứng dụng đó.') }}</p>
                    </div>
                    @php($inAppShare = collect($stats['apps'])->where('in_app', true)->sum('percent'))
                    <div class="text-right shrink-0">
                        <p class="text-[11px] uppercase tracking-wide text-base-content/45">{{ __('Quét trong ứng dụng') }}</p>
                        <p class="font-semibold tabular-nums">{{ number_format($inAppShare, 1) }}%</p>
                    </div>
                </div>
                <div class="overflow-x-auto">
                    <table class="table table-sm">
                        <thead class="text-xs">
                            <tr>
                                <th>{{ __('Ứng dụng') }}</th>
                                <th class="w-2/5">{{ __('Lượt quét') }}</th>
                                <th class="text-right">{{ __('Khách') }}</th>
                                <th class="text-right">iOS</th>
                                <th class="text-right">Android</th>
                                <th class="text-right">{{ __('Khác') }}</th>
                            </tr>
                        </thead>
                        <tbody>
                        @foreach($stats['apps'] as $row)
                            <tr class="hover">
                                <td class="whitespace-nowrap">
                                    <a href="{{ route('backend.qr-scans.index', [...$query, 'app' => $row['key'] === 'unknown' ? null : $row['key']]) }}" class="font-medium hover:text-primary">{{ $row['label'] }}</a>
                                    <span @class(['badge badge-xs ml-1', 'badge-warning badge-soft' => $row['in_app'], 'badge-ghost' => ! $row['in_app']])>{{ $row['in_app'] ? __('Trong app') : __('Trình duyệt') }}</span>
                                </td>
                                <td>
                                    <div class="flex items-center gap-2">
                                        <progress class="progress progress-primary h-1.5 flex-1" value="{{ $row['total'] }}" max="{{ $appMax }}"></progress>
                                        <span class="tabular-nums font-semibold w-10 text-right">{{ number_format($row['total']) }}</span>
                                        <span class="tabular-nums text-xs text-base-content/50 w-12 text-right">{{ $row['percent'] }}%</span>
                                    </div>
                                </td>
                                <td class="text-right tabular-nums">{{ number_format($row['visitors']) }}</td>
                                <td class="text-right tabular-nums text-base-content/70">{{ $row['ios'] ?: '—' }}</td>
                                <td class="text-right tabular-nums text-base-content/70">{{ $row['android'] ?: '—' }}</td>
                                <td class="text-right tabular-nums text-base-content/70">{{ $row['other'] ?: '—' }}</td>
                            </tr>
                        @endforeach
                        </tbody>
                    </table>
                </div>
            </div>
        </section>

        <section class="card bg-base-100 border border-base-200 xl:col-span-4 min-w-0">
            <div class="card-body p-4 gap-4">
                <h2 class="font-semibold">{{ __('Thiết bị quét') }}</h2>
                <div>
                    <p class="text-xs font-medium text-base-content/55 mb-1.5">{{ __('Hệ điều hành') }}</p>
                    <ul class="space-y-2">
                        @foreach($stats['audience']['os'] as $row)
                        <li>
                            <div class="flex items-center justify-between gap-2 text-sm">
                                <span class="font-medium">{{ $row['label'] }}</span>
                                <span class="tabular-nums">{{ number_format($row['total']) }} <span class="text-base-content/45">· {{ $row['percent'] }}%</span></span>
                            </div>
                            <progress class="progress progress-primary h-1.5 w-full" value="{{ $row['percent'] }}" max="100"></progress>
                            @if($row['versions'])
                            <p class="text-[11px] text-base-content/50">
                                @foreach($row['versions'] as $version){{ $row['label'] }} {{ $version['version'] }}: {{ number_format($version['total']) }}@if(! $loop->last) · @endif @endforeach
                            </p>
                            @endif
                        </li>
                        @endforeach
                    </ul>
                </div>
                @foreach($audience as $title => $rows)
                <div>
                    <p class="text-xs font-medium text-base-content/55 mb-1.5">{{ $title }}</p>
                    <div class="flex h-2.5 w-full overflow-hidden rounded-full bg-base-200">
                        @foreach($rows as $row)
                        <span class="h-full" style="width: {{ $row['percent'] }}%; background: {{ $palette[$loop->index % count($palette)] }}" title="{{ $row['label'] }}: {{ $row['percent'] }}%"></span>
                        @endforeach
                    </div>
                    <ul class="mt-2 grid grid-cols-2 gap-x-3 gap-y-1 text-xs">
                        @foreach($rows as $row)
                        <li class="flex items-center gap-1.5 min-w-0">
                            <span class="w-2 h-2 rounded-full shrink-0" style="background: {{ $palette[$loop->index % count($palette)] }}"></span>
                            <span class="truncate">{{ $row['label'] }}</span>
                            <span class="ml-auto tabular-nums text-base-content/55">{{ $row['percent'] }}%</span>
                        </li>
                        @endforeach
                    </ul>
                </div>
                @endforeach
            </div>
        </section>

        <section class="card bg-base-100 border border-base-200 {{ $identity ? 'xl:col-span-12' : 'xl:col-span-8' }} min-w-0">
            <div class="card-body p-4 gap-3">
                <div class="flex flex-wrap items-start justify-between gap-2">
                    <div>
                        <h2 class="font-semibold">{{ __('Thời điểm quét') }}</h2>
                        <p class="text-xs text-base-content/50">{{ __('Theo múi giờ hệ thống') }}</p>
                    </div>
                    <div class="flex gap-4 text-right">
                        @if($stats['timing']['peak_hour'] !== null)
                        <div><p class="text-[11px] uppercase tracking-wide text-base-content/45">{{ __('Giờ cao điểm') }}</p><p class="font-semibold tabular-nums">{{ sprintf('%02d:00–%02d:59', $stats['timing']['peak_hour'], $stats['timing']['peak_hour']) }}</p></div>
                        @endif
                        @if($stats['timing']['peak_weekday'] !== null)
                        <div><p class="text-[11px] uppercase tracking-wide text-base-content/45">{{ __('Ngày cao điểm') }}</p><p class="font-semibold">{{ $weekdays[$stats['timing']['peak_weekday']] }}</p></div>
                        @endif
                    </div>
                </div>
                <div class="grid grid-cols-1 md:grid-cols-[2fr_1fr] gap-4 min-w-0">
                    <div id="qr-scan-hours" class="w-full h-56 min-w-0"></div>
                    <div id="qr-scan-weekdays" class="w-full h-56 min-w-0"></div>
                </div>
            </div>
        </section>

        @unless($identity)
        <section class="card bg-base-100 border border-base-200 xl:col-span-4 min-w-0">
            <div class="card-body p-4 gap-3">
                <h2 class="font-semibold">{{ __('Theo loại đối tượng') }}</h2>
                <ul class="space-y-3">
                    @foreach($stats['kinds'] as $row)
                    <li>
                        <div class="flex items-center justify-between gap-2 text-sm">
                            <span class="truncate font-medium">{{ $row['label'] }}</span>
                            <span class="tabular-nums">{{ number_format($row['total']) }} <span class="text-base-content/45">· {{ $row['percent'] }}%</span></span>
                        </div>
                        <progress class="progress progress-primary h-1.5 w-full" value="{{ $row['percent'] }}" max="100"></progress>
                        <p class="text-[11px] text-base-content/45">{{ __(':count mã có lượt quét', ['count' => number_format($row['codes'])]) }}</p>
                    </li>
                    @endforeach
                </ul>
            </div>
        </section>
        <section class="card bg-base-100 border border-base-200 xl:col-span-12 min-w-0">
            <div class="card-body p-0">
                <div class="flex items-center justify-between gap-3 px-4 pt-4 pb-2">
                    <div>
                        <h2 class="font-semibold">{{ __('Mã được quét nhiều nhất') }}</h2>
                        <p class="text-xs text-base-content/50">{{ __('Top :count trong kỳ — bấm mã để xem chi tiết', ['count' => \App\Shared\Traceability\Actions\BuildQrScanStatsAction::TOP_LIMIT]) }}</p>
                    </div>
                </div>
                <div class="overflow-x-auto">
                    <table class="table table-sm">
                        <thead class="text-xs">
                            <tr>
                                <th class="w-8">#</th>
                                <th>{{ __('Mã / Đối tượng') }}</th>
                                <th class="w-44">{{ __('Lượt quét') }}</th>
                                <th class="text-right">{{ __('Khách') }}</th>
                                <th class="text-right">{{ __('Trực tiếp') }}</th>
                                <th class="text-right">{{ __('Quét gần nhất') }}</th>
                            </tr>
                        </thead>
                        <tbody>
                        @foreach($stats['top'] as $row)
                            <tr class="hover">
                                <td class="text-base-content/40 tabular-nums">{{ $loop->iteration }}</td>
                                <td class="min-w-60">
                                    <a href="{{ route('backend.qr-scans.index', [...$query, 'code' => $row['qr_code']]) }}" class="font-mono text-xs font-semibold link link-primary">{{ $row['qr_code'] }}</a>
                                    <span class="text-xs text-base-content/45">· {{ $row['kind'] }}</span>
                                    <p class="font-medium leading-snug line-clamp-1">{{ $row['title'] }}
                                        @if($row['trashed'])<span class="badge badge-ghost badge-xs">{{ __('đã xoá') }}</span>@endif
                                        @if($row['revoked'])<span class="badge badge-error badge-xs">{{ __('Đã thu hồi') }}</span>@endif
                                    </p>
                                </td>
                                <td>
                                    <div class="flex items-center gap-2">
                                        <span class="font-semibold tabular-nums w-10 text-right">{{ number_format($row['scans']) }}</span>
                                        <progress class="progress progress-primary h-1.5 flex-1" value="{{ $row['share'] }}" max="{{ $stats['top'][0]['share'] ?: 100 }}"></progress>
                                    </div>
                                    <p class="text-[11px] text-base-content/45 pl-12">{{ $row['share'] }}% {{ __('tổng') }}</p>
                                </td>
                                <td class="text-right tabular-nums">{{ number_format($row['visitors']) }}</td>
                                <td class="text-right tabular-nums">{{ $row['direct_share'] }}%</td>
                                <td class="text-right whitespace-nowrap text-xs text-base-content/55">{{ $row['last_scanned_at'] }}</td>
                            </tr>
                        @endforeach
                        </tbody>
                    </table>
                </div>
            </div>
        </section>

        @endunless
    </div>
    @endif

    @if($hasData)
    <section class="card bg-base-100 border border-base-200">
        <div class="card-body p-0 overflow-hidden tabulator-daisy">
            <div class="flex flex-wrap items-center justify-between gap-3 px-4 pt-4 pb-2">
                <div>
                    <h2 class="font-semibold">{{ __('Lịch sử quét') }}</h2>
                    <p class="text-xs text-base-content/50">{{ __('Từng lượt quét theo bộ lọc hiện tại, mới nhất trước') }}</p>
                </div>
                <a href="{{ route('backend.qr-scans.export', $query) }}" class="btn btn-xs btn-ghost">{{ __('Xuất CSV') }}</a>
            </div>
            <div id="qr-scan-history"
                 data-url="{{ route('backend.qr-scans.history') }}"
                 data-params="{{ json_encode($historyParams) }}"
                 data-source-badges="{{ json_encode($sourceBadges) }}"
                 data-show-target="{{ $identity ? '0' : '1' }}"></div>
        </div>
    </section>

    <script type="application/json" id="qr-scan-stats">{!! json_encode([
        'trend' => $stats['trend'],
        'sources' => array_map(fn ($row) => [...$row, 'color' => $sourceColors[$row['key']] ?? $sourceColors['unknown']], $stats['sources']),
        'sourceColors' => $sourceColors,
        'hours' => $stats['timing']['hours'],
        'weekdays' => $stats['timing']['weekdays'],
        'weekdayLabels' => $weekdays,
        'labels' => ['visitors' => __('Khách'), 'scans' => __('Lượt quét')],
    ], JSON_HEX_TAG | JSON_HEX_AMP | JSON_UNESCAPED_UNICODE) !!}</script>
    @endif
    @endif
</div>
@endsection

@push('styles')
    <x-tabulator-theme />
@endpush

@push('scripts')
    @vite([
        'resources/js/modules/echarts.js',
        'resources/js/modules/tabulator.js',
        'resources/js/pages/qr-scan-index.js',
    ], 'build/backend')
@endpush
