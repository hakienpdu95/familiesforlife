@extends('layouts.backend')
@section('title', 'AI Impact — Tác động kinh doanh')

@push('styles')
    @vite(['Modules/Assessment/resources/assets/sass/assessment.scss'], 'build/backend')
@endpush

@section('content')

@if(session('success'))
<div class="alert alert-success mb-4 py-2 px-4 text-sm">{{ session('success') }}</div>
@endif
@if(session('info'))
<div class="alert alert-warning mb-4 py-2 px-4 text-sm">{{ session('info') }}</div>
@endif
@if(session('error'))
<div class="alert alert-error mb-4 py-2 px-4 text-sm">{{ session('error') }}</div>
@endif

{{-- ── Page header ─────────────────────────────────────────────────────────── --}}
<div class="flex flex-wrap items-center justify-between gap-3 mb-6">
    <div>
        <h1 class="text-2xl font-bold text-base-content">AI Impact Tracker</h1>
        <p class="text-sm text-base-content/50 mt-0.5">Ghi nhận và theo dõi tác động kinh doanh từ ứng dụng AI</p>
    </div>
    <div class="flex gap-2">
        <a href="{{ route('backend.ai-impact.import-form') }}" class="btn btn-ghost btn-sm gap-1.5">
            <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-8l-4-4m0 0L8 8m4-4v12"/></svg>
            Import CSV
        </a>
        <a href="{{ route('backend.ai-impact.create') }}" class="btn btn-primary btn-sm gap-1.5">
            <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/></svg>
            Ghi nhận tác động
        </a>
    </div>
</div>

{{-- ── Summary cards ───────────────────────────────────────────────────────── --}}
<div class="grid grid-cols-2 sm:grid-cols-4 gap-3 mb-6">
    <div class="stat bg-base-100 border border-base-200 rounded-xl py-3 px-4 shadow-sm">
        <div class="stat-title text-xs">Tổng bản ghi</div>
        <div class="stat-value text-2xl text-primary">{{ $snapshots->total() }}</div>
    </div>
    <div class="stat bg-base-100 border border-base-200 rounded-xl py-3 px-4 shadow-sm">
        <div class="stat-title text-xs">Cải thiện TB</div>
        <div class="stat-value text-2xl text-success">
            {{ $stats['avgImprovement'] ? number_format($stats['avgImprovement'], 1) . '%' : '—' }}
        </div>
    </div>
    <div class="stat bg-base-100 border border-base-200 rounded-xl py-3 px-4 shadow-sm">
        <div class="stat-title text-xs">ROI trung bình</div>
        <div class="stat-value text-2xl text-accent">
            {{ $stats['avgRoi'] ? number_format($stats['avgRoi'] / 100, 1) . 'x' : '—' }}
        </div>
    </div>
    <div class="stat bg-base-100 border border-base-200 rounded-xl py-3 px-4 shadow-sm">
        <div class="stat-title text-xs">Tuần này</div>
        <div class="stat-value text-2xl">{{ $stats['thisWeek'] ?? 0 }}</div>
    </div>
</div>

{{-- ── Part 4: Category chart ──────────────────────────────────────────────── --}}
@if($chartData->count())
<div class="card bg-base-100 shadow-sm border border-base-200 mb-5">
    <div class="card-body">
        <h2 class="card-title text-sm mb-3">Cải thiện trung bình theo danh mục</h2>
        @php
        $catMeta = [
            'learning'     => ['Học tập',     'bg-info/70',    'text-info'],
            'productivity' => ['Năng suất',   'bg-success/70', 'text-success'],
            'quality'      => ['Chất lượng',  'bg-accent/70',  'text-accent'],
            'ai_adoption'  => ['Ứng dụng AI', 'bg-primary/70', 'text-primary'],
            'business'     => ['Kinh doanh',  'bg-warning/70', 'text-warning'],
        ];
        $maxVal = $chartData->max(fn($d) => abs($d->avg_improvement)) ?: 1;
        @endphp
        <div class="space-y-2">
            @foreach($catMeta as $key => [$label, $bgCls, $textCls])
            @php $d = $chartData[$key] ?? null; @endphp
            <div class="flex items-center gap-3">
                <span class="text-xs {{ $textCls }} font-medium w-24 shrink-0">{{ $label }}</span>
                <div class="flex-1 h-5 bg-base-200 rounded overflow-hidden relative">
                    @if($d)
                    <div class="{{ $bgCls }} h-full rounded transition-all"
                         style="width: {{ min(abs($d->avg_improvement) / $maxVal * 100, 100) }}%"></div>
                    @endif
                </div>
                <span class="text-xs font-mono w-16 text-right {{ $textCls }}">
                    @if($d)
                        {{ $d->avg_improvement >= 0 ? '+' : '' }}{{ number_format($d->avg_improvement, 1) }}%
                        <span class="text-base-content/30">({{ $d->cnt }})</span>
                    @else —
                    @endif
                </span>
            </div>
            @endforeach
        </div>
    </div>
</div>
@endif

{{-- ── Part 3: Filters ──────────────────────────────────────────────────────── --}}
<form method="GET" action="{{ route('backend.ai-impact.index') }}"
      class="card bg-base-100 shadow-sm border border-base-200 mb-4">
    <div class="card-body p-3">
        <div class="flex flex-wrap gap-2 items-end">
            <div class="form-control">
                <label class="label py-0.5"><span class="label-text text-xs">Danh mục</span></label>
                <select name="category" class="select select-bordered select-xs min-w-32">
                    <option value="">Tất cả</option>
                    @foreach([
                        ['learning','Học tập'],['productivity','Năng suất'],
                        ['quality','Chất lượng'],['ai_adoption','Ứng dụng AI'],['business','Kinh doanh'],
                    ] as [$val, $lbl])
                    <option value="{{ $val }}" {{ request('category') === $val ? 'selected' : '' }}>{{ $lbl }}</option>
                    @endforeach
                </select>
            </div>
            <div class="form-control">
                <label class="label py-0.5"><span class="label-text text-xs">Nhân viên</span></label>
                <select name="employee_id" class="select select-bordered select-xs min-w-36">
                    <option value="">Tất cả</option>
                    @foreach($employees as $emp)
                    <option value="{{ $emp->id }}" {{ request('employee_id') == $emp->id ? 'selected' : '' }}>{{ $emp->full_name }}</option>
                    @endforeach
                </select>
            </div>
            <div class="form-control">
                <label class="label py-0.5"><span class="label-text text-xs">Từ ngày</span></label>
                <input type="date" name="period_from" class="input input-bordered input-xs"
                       value="{{ request('period_from') }}">
            </div>
            <div class="form-control">
                <label class="label py-0.5"><span class="label-text text-xs">Đến ngày</span></label>
                <input type="date" name="period_to" class="input input-bordered input-xs"
                       value="{{ request('period_to') }}">
            </div>
            <button type="submit" class="btn btn-sm btn-neutral">Lọc</button>
            @if(request()->hasAny(['category','employee_id','period_from','period_to']))
            <a href="{{ route('backend.ai-impact.index') }}" class="btn btn-sm btn-ghost">Xoá lọc</a>
            @endif
        </div>
    </div>
</form>

{{-- ── Snapshots list ───────────────────────────────────────────────────────── --}}
<div class="card bg-base-100 shadow-sm border border-base-200">
    <div class="card-body p-0">
        @if($snapshots->count())
        <div class="overflow-x-auto">
            <table class="table table-sm">
                <thead>
                    <tr class="text-xs text-base-content/50 border-b border-base-200">
                        <th class="px-5 py-3">Nhân viên</th>
                        <th>Danh mục</th>
                        <th>Chỉ số đo</th>
                        <th class="text-center">Cải thiện</th>
                        <th class="text-center">ROI</th>
                        <th>Kỳ</th>
                        <th></th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($snapshots as $snap)
                    @php
                        $catLabels = [
                            'learning'     => ['Học tập',     'badge-info'],
                            'productivity' => ['Năng suất',   'badge-success'],
                            'quality'      => ['Chất lượng',  'badge-accent'],
                            'ai_adoption'  => ['Ứng dụng AI', 'badge-primary'],
                            'business'     => ['Kinh doanh',  'badge-warning'],
                        ];
                        $cat = $catLabels[$snap->impact_category] ?? [$snap->impact_category, 'badge-ghost'];
                    @endphp
                    <tr class="hover:bg-base-50 border-b border-base-200/50 last:border-0">
                        <td class="px-5 py-3">
                            @if($snap->employee)
                            <a href="{{ route('backend.ai-impact.employee', $snap->employee) }}"
                               class="flex items-center gap-2.5 hover:text-primary">
                                <div class="w-7 h-7 rounded-full bg-primary/10 flex items-center justify-center text-xs font-semibold text-primary shrink-0">
                                    {{ strtoupper(substr($snap->employee->full_name, 0, 1)) }}
                                </div>
                                <div>
                                    <p class="font-medium text-sm leading-tight">{{ $snap->employee->full_name }}</p>
                                    <p class="text-xs text-base-content/40">{{ $snap->employee->snap_job_title ?? '' }}</p>
                                </div>
                            </a>
                            @else
                            <span class="text-base-content/30 text-sm">—</span>
                            @endif
                        </td>
                        <td>
                            <span class="badge {{ $cat[1] }} badge-xs">{{ $cat[0] }}</span>
                        </td>
                        <td class="text-sm text-base-content/70 max-w-36 truncate" title="{{ $snap->impact_type }}">
                            {{ $snap->impact_type ?? '—' }}
                        </td>
                        <td class="text-center">
                            @if($snap->improvement_pct !== null)
                            <span class="font-semibold {{ $snap->improvement_pct >= 0 ? 'text-success' : 'text-error' }}">
                                {{ $snap->improvement_pct >= 0 ? '+' : '' }}{{ number_format($snap->improvement_pct, 1) }}%
                            </span>
                            @else
                            <span class="text-base-content/30">—</span>
                            @endif
                        </td>
                        <td class="text-center">
                            @if($snap->roi_pct !== null)
                            <span class="font-semibold text-accent">{{ number_format($snap->roi_pct / 100, 1) }}x</span>
                            @else
                            <span class="text-base-content/30">—</span>
                            @endif
                        </td>
                        <td class="text-xs text-base-content/40 whitespace-nowrap">
                            {{ $snap->period_start?->format('d/m/Y') }}
                        </td>
                        <td class="pr-4">
                            <div class="flex gap-1">
                                <a href="{{ route('backend.ai-impact.edit', $snap) }}" class="btn btn-ghost btn-xs">Sửa</a>
                                <form method="POST" action="{{ route('backend.ai-impact.destroy', $snap) }}"
                                      onsubmit="return confirm('Xoá bản ghi này?')">
                                    @csrf @method('DELETE')
                                    <button class="btn btn-ghost btn-xs text-error">Xoá</button>
                                </form>
                            </div>
                        </td>
                    </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
        @if($snapshots->hasPages())
        <div class="px-5 py-4 border-t border-base-200">
            {{ $snapshots->links() }}
        </div>
        @endif
        @else
        <div class="items-center text-center py-14">
            <svg class="w-12 h-12 text-base-content/20 mx-auto mb-3" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z"/></svg>
            <p class="text-base-content/50 text-sm mb-3">
                {{ request()->hasAny(['category','employee_id','period_from','period_to']) ? 'Không có kết quả phù hợp.' : 'Chưa có bản ghi tác động AI nào.' }}
            </p>
            <a href="{{ route('backend.ai-impact.create') }}" class="btn btn-primary btn-sm">Ghi nhận ngay</a>
        </div>
        @endif
    </div>
</div>

@endsection

@push('scripts')
    @vite(['Modules/Assessment/resources/assets/js/assessment.js'], 'build/backend')
@endpush
