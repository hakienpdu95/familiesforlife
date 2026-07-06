@extends('layouts.backend')
@section('title', 'Analytics — SOP')


@section('content')
@php
$statusLabel = [
    'draft'          => ['label' => 'Bản nháp',      'class' => 'badge-ghost'],
    'pending_review' => ['label' => 'Chờ duyệt',     'class' => 'badge-warning'],
    'approved'       => ['label' => 'Đã duyệt',      'class' => 'badge-success'],
    'rejected'       => ['label' => 'Từ chối',        'class' => 'badge-error'],
    'archived'       => ['label' => 'Đã lưu trữ',    'class' => 'badge-neutral'],
];
$stepTypeLabel = [
    'start'        => ['label' => 'Bắt đầu',      'color' => '#1D9E75'],
    'end'          => ['label' => 'Kết thúc',     'color' => '#1D9E75'],
    'action'       => ['label' => 'Hành động',    'color' => '#378ADD'],
    'decision'     => ['label' => 'Quyết định',   'color' => '#EF9F27'],
    'sub_sop'      => ['label' => 'Sub-SOP',      'color' => '#1D9E75'],
    'notification' => ['label' => 'Thông báo',    'color' => '#7F77DD'],
    'wait'         => ['label' => 'Chờ',          'color' => '#888780'],
];
$maxDuration = $durationByType->max('avg_duration') ?: 1;
$maxTypeCount = $stepTypeCounts->max() ?: 1;
@endphp

<div>
    {{-- ── Header ──────────────────────────────────────────────────────────── --}}
    <div class="flex flex-wrap items-center justify-between gap-3 mb-6">
        <div>
            <h1 class="text-2xl font-bold text-base-content">Analytics — SOP</h1>
            <p class="text-sm text-base-content/50 mt-0.5">Thống kê tổng quan quy trình vận hành</p>
        </div>
        <a href="{{ route('backend.sop.index') }}" class="btn btn-ghost btn-sm gap-1.5">
            <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"/>
            </svg>
            Danh sách SOP
        </a>
    </div>

    {{-- ── Stat cards ───────────────────────────────────────────────────────── --}}
    <div class="grid grid-cols-2 lg:grid-cols-4 gap-4 mb-6">

        {{-- Total --}}
        <div class="card bg-base-100 border border-base-200 shadow-sm">
            <div class="card-body p-4">
                <p class="text-xs text-base-content/50 font-medium uppercase tracking-wide">Tổng SOP</p>
                <p class="text-3xl font-bold text-base-content mt-1">{{ $totalSops }}</p>
                <p class="text-xs text-base-content/40 mt-1">Tất cả trạng thái</p>
            </div>
        </div>

        {{-- Approved --}}
        <div class="card bg-base-100 border border-base-200 shadow-sm">
            <div class="card-body p-4">
                <p class="text-xs text-base-content/50 font-medium uppercase tracking-wide">Đã duyệt</p>
                <p class="text-3xl font-bold text-success mt-1">{{ $statusCounts->get('approved', 0) }}</p>
                @if($totalSops > 0)
                <p class="text-xs text-base-content/40 mt-1">{{ round($statusCounts->get('approved', 0) / $totalSops * 100) }}% tổng số</p>
                @endif
            </div>
        </div>

        {{-- Pending review --}}
        <div class="card bg-base-100 border border-base-200 shadow-sm">
            <div class="card-body p-4">
                <p class="text-xs text-base-content/50 font-medium uppercase tracking-wide">Chờ duyệt</p>
                <p class="text-3xl font-bold text-warning mt-1">{{ $statusCounts->get('pending_review', 0) }}</p>
                <p class="text-xs text-base-content/40 mt-1">Cần xử lý</p>
            </div>
        </div>

        {{-- Avg steps --}}
        <div class="card bg-base-100 border border-base-200 shadow-sm">
            <div class="card-body p-4">
                <p class="text-xs text-base-content/50 font-medium uppercase tracking-wide">Bước/SOP trung bình</p>
                <p class="text-3xl font-bold text-primary mt-1">{{ $avgSteps }}</p>
                <p class="text-xs text-base-content/40 mt-1">Tất cả SOP có bước</p>
            </div>
        </div>
    </div>

    {{-- ── Main grid ─────────────────────────────────────────────────────────── --}}
    <div class="grid grid-cols-1 lg:grid-cols-3 gap-5">

        {{-- Left col (2/3): Charts ──────────────────────────────────────────── --}}
        <div class="lg:col-span-2 space-y-5">

            {{-- Duration by step type — bar chart (inline SVG) --}}
            <div class="card bg-base-100 border border-base-200 shadow-sm">
                <div class="card-body p-5">
                    <h2 class="text-sm font-semibold text-base-content mb-4">
                        Thời gian trung bình theo loại bước (phút) — SOP đã duyệt
                    </h2>
                    @if($durationByType->isEmpty())
                        <div class="py-10 text-center text-sm text-base-content/30">
                            Chưa có dữ liệu (cần SOP đã duyệt với bước có thời gian)
                        </div>
                    @else
                    @php
                        $barH   = 28;
                        $gap    = 12;
                        $labelW = 100;
                        $chartW = 380;
                        $svgH   = $durationByType->count() * ($barH + $gap) + $gap;
                    @endphp
                    <div class="overflow-x-auto">
                        <svg width="{{ $labelW + $chartW + 60 }}" height="{{ $svgH }}" xmlns="http://www.w3.org/2000/svg"
                             style="font-family: sans-serif;">
                            @foreach($durationByType as $i => $row)
                            @php
                                $y      = $gap + $i * ($barH + $gap);
                                $barW   = (float)$row->avg_duration / $maxDuration * $chartW;
                                $label  = $stepTypeLabel[$row->step_type]['label'] ?? $row->step_type;
                                $color  = $stepTypeLabel[$row->step_type]['color'] ?? '#378ADD';
                            @endphp
                            {{-- Label --}}
                            <text x="{{ $labelW - 8 }}" y="{{ $y + $barH / 2 + 1 }}"
                                  text-anchor="end" dominant-baseline="middle"
                                  font-size="11" fill="#888">{{ $label }}</text>
                            {{-- Bar --}}
                            <rect x="{{ $labelW }}" y="{{ $y }}"
                                  width="{{ max($barW, 4) }}" height="{{ $barH }}"
                                  rx="4" fill="{{ $color }}" opacity="0.85"/>
                            {{-- Value --}}
                            <text x="{{ $labelW + max($barW, 4) + 6 }}" y="{{ $y + $barH / 2 + 1 }}"
                                  dominant-baseline="middle" font-size="11" fill="#555">
                                {{ round($row->avg_duration, 1) }} ph
                                <tspan font-size="10" fill="#aaa"> ({{ $row->step_count }} bước)</tspan>
                            </text>
                            @endforeach
                        </svg>
                    </div>
                    @endif
                </div>
            </div>

            {{-- Step type distribution — horizontal bar chart --}}
            <div class="card bg-base-100 border border-base-200 shadow-sm">
                <div class="card-body p-5">
                    <h2 class="text-sm font-semibold text-base-content mb-4">
                        Phân bố loại bước — tổng số bước đang active
                    </h2>
                    @if($stepTypeCounts->isEmpty())
                        <div class="py-10 text-center text-sm text-base-content/30">
                            Chưa có bước nào trong hệ thống
                        </div>
                    @else
                    @php
                        $barH2  = 24;
                        $gap2   = 10;
                        $svgH2  = $stepTypeCounts->count() * ($barH2 + $gap2) + $gap2;
                        $totalSteps = $stepTypeCounts->sum();
                    @endphp
                    <div class="overflow-x-auto">
                        <svg width="{{ $labelW + $chartW + 80 }}" height="{{ $svgH2 }}" xmlns="http://www.w3.org/2000/svg"
                             style="font-family: sans-serif;">
                            @foreach($stepTypeCounts as $type => $count)
                            @php
                                $i      = $loop->index;
                                $y2     = $gap2 + $i * ($barH2 + $gap2);
                                $barW2  = (int)$count / $maxTypeCount * $chartW;
                                $label2 = $stepTypeLabel[$type]['label'] ?? $type;
                                $color2 = $stepTypeLabel[$type]['color'] ?? '#378ADD';
                                $pct    = $totalSteps > 0 ? round($count / $totalSteps * 100) : 0;
                            @endphp
                            <text x="{{ $labelW - 8 }}" y="{{ $y2 + $barH2 / 2 + 1 }}"
                                  text-anchor="end" dominant-baseline="middle"
                                  font-size="11" fill="#888">{{ $label2 }}</text>
                            <rect x="{{ $labelW }}" y="{{ $y2 }}"
                                  width="{{ max($barW2, 4) }}" height="{{ $barH2 }}"
                                  rx="4" fill="{{ $color2 }}" opacity="0.8"/>
                            <text x="{{ $labelW + max($barW2, 4) + 6 }}" y="{{ $y2 + $barH2 / 2 + 1 }}"
                                  dominant-baseline="middle" font-size="11" fill="#555">
                                {{ $count }}
                                <tspan font-size="10" fill="#aaa"> ({{ $pct }}%)</tspan>
                            </text>
                            @endforeach
                        </svg>
                    </div>
                    @endif
                </div>
            </div>

            {{-- Status distribution --}}
            <div class="card bg-base-100 border border-base-200 shadow-sm">
                <div class="card-body p-5">
                    <h2 class="text-sm font-semibold text-base-content mb-4">Phân bố trạng thái SOP</h2>
                    @if($totalSops === 0)
                        <div class="py-10 text-center text-sm text-base-content/30">Chưa có SOP nào</div>
                    @else
                    <div class="space-y-3">
                        @foreach($statusLabel as $status => $meta)
                        @php $cnt = $statusCounts->get($status, 0); $pct = $totalSops > 0 ? $cnt / $totalSops * 100 : 0; @endphp
                        @if($cnt > 0)
                        <div>
                            <div class="flex justify-between text-xs mb-1">
                                <span class="font-medium text-base-content/70">{{ $meta['label'] }}</span>
                                <span class="text-base-content/40">{{ $cnt }} ({{ round($pct) }}%)</span>
                            </div>
                            <div class="h-2 bg-base-200 rounded-full overflow-hidden">
                                <div class="h-full rounded-full transition-all" style="width: {{ round($pct) }}%; background: {{ match($status) { 'approved' => '#22c55e', 'pending_review' => '#f59e0b', 'rejected' => '#ef4444', 'archived' => '#9ca3af', default => '#d1d5db' } }}"></div>
                            </div>
                        </div>
                        @endif
                        @endforeach
                    </div>
                    @endif
                </div>
            </div>
        </div>

        {{-- Right col (1/3): Lists ──────────────────────────────────────────── --}}
        <div class="space-y-5">

            {{-- Expiring soon --}}
            <div class="card bg-base-100 border border-base-200 shadow-sm">
                <div class="card-body p-5">
                    <h2 class="text-sm font-semibold text-base-content mb-3 flex items-center gap-2">
                        <svg class="w-4 h-4 text-warning" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01M10.29 3.86L1.82 18a2 2 0 001.71 3h16.94a2 2 0 001.71-3L13.71 3.86a2 2 0 00-3.42 0z"/>
                        </svg>
                        Sắp hết hiệu lực (7 ngày)
                    </h2>
                    @if($expiringSoon->isEmpty())
                        <p class="text-sm text-base-content/30 py-6 text-center">Không có SOP nào sắp hết hạn</p>
                    @else
                        <div class="space-y-2">
                            @foreach($expiringSoon as $s)
                            <div class="flex flex-col gap-0.5 py-2 border-b border-base-200 last:border-0">
                                <a href="{{ route('backend.sop.show', $s->uuid) }}"
                                   class="text-sm font-medium text-primary hover:underline truncate">
                                    {{ $s->code }} — {{ $s->title }}
                                </a>
                                <div class="flex items-center gap-2 text-xs text-base-content/40">
                                    <span class="text-warning font-medium">Hết hạn {{ \Carbon\Carbon::parse($s->expired_date)->format('d/m/Y') }}</span>
                                    @if($s->owner)
                                    <span>· {{ $s->owner->name }}</span>
                                    @endif
                                </div>
                            </div>
                            @endforeach
                        </div>
                    @endif
                </div>
            </div>

            {{-- Recent SOPs --}}
            <div class="card bg-base-100 border border-base-200 shadow-sm">
                <div class="card-body p-5">
                    <h2 class="text-sm font-semibold text-base-content mb-3">SOP mới nhất</h2>
                    @if($recentSops->isEmpty())
                        <p class="text-sm text-base-content/30 py-6 text-center">Chưa có SOP nào</p>
                    @else
                        <div class="space-y-2">
                            @foreach($recentSops as $s)
                            @php
                                $sc = match($s->status?->value ?? $s->status) {
                                    'approved'       => 'badge-success',
                                    'pending_review' => 'badge-warning',
                                    'rejected'       => 'badge-error',
                                    'archived'       => 'badge-neutral',
                                    default          => 'badge-ghost',
                                };
                            @endphp
                            <div class="flex flex-col gap-0.5 py-2 border-b border-base-200 last:border-0">
                                <div class="flex items-center gap-2">
                                    <a href="{{ route('backend.sop.show', $s->uuid) }}"
                                       class="text-sm font-medium text-primary hover:underline truncate flex-1 min-w-0">
                                        {{ $s->code }}
                                    </a>
                                    <span class="badge badge-xs {{ $sc }} shrink-0">
                                        {{ $statusLabel[$s->status?->value ?? $s->status]['label'] ?? $s->status }}
                                    </span>
                                </div>
                                <p class="text-xs text-base-content/50 truncate">{{ $s->title }}</p>
                                <p class="text-xs text-base-content/30">{{ $s->created_at?->format('d/m/Y') }}</p>
                            </div>
                            @endforeach
                        </div>
                        <a href="{{ route('backend.sop.index') }}" class="btn btn-ghost btn-xs w-full mt-2">
                            Xem tất cả
                        </a>
                    @endif
                </div>
            </div>

        </div>
    </div>
</div>
@endsection
