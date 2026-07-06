@extends('layouts.backend')
@section('title', 'Quản lý Chứng nhận AI')

@section('content')

@if(session('success'))
<div class="alert alert-success mb-4 py-2 px-4 text-sm">{{ session('success') }}</div>
@endif
@if(session('error'))
<div class="alert alert-error mb-4 py-2 px-4 text-sm">{{ session('error') }}</div>
@endif

<div class="flex flex-wrap items-center justify-between gap-3 mb-6">
    <div>
        <h1 class="text-xl font-bold">Quản lý Chứng nhận AI</h1>
        <p class="text-sm text-base-content/50 mt-0.5">Định nghĩa chứng nhận & cấp phát cho nhân viên</p>
    </div>
    <div class="flex gap-2">
        <a href="{{ route('backend.certs-admin.issued') }}" class="btn btn-ghost btn-sm gap-1.5">
            <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2m-3 7h3m-3 4h3m-6-4h.01M9 16h.01"/></svg>
            Danh sách đã cấp
        </a>
        <a href="{{ route('backend.certs-admin.issue-form') }}" class="btn btn-outline btn-sm gap-1.5">
            <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/></svg>
            Cấp cert thủ công
        </a>
        <a href="{{ route('backend.certs-admin.def.create') }}" class="btn btn-primary btn-sm gap-1.5">
            <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/></svg>
            Thêm định nghĩa
        </a>
    </div>
</div>

{{-- Stats --}}
<div class="grid grid-cols-3 gap-3 mb-6">
    @foreach([
        ['Cert đang hiệu lực', $stats['total_active'], 'text-success'],
        ['Tổng đã cấp', $stats['total_issued'], 'text-primary'],
        ['Hết hạn trong 30 ngày', $stats['expiring_soon'], 'text-warning'],
    ] as [$label, $val, $cls])
    <div class="stat bg-base-100 border border-base-200 rounded-xl py-3 px-4 shadow-sm">
        <div class="stat-title text-xs">{{ $label }}</div>
        <div class="stat-value text-2xl {{ $cls }}">{{ $val }}</div>
    </div>
    @endforeach
</div>

@php
$levelBadge = ['FOUNDATION'=>['Foundation','badge-info'],'PRACTITIONER'=>['Practitioner','badge-warning'],'PROFESSIONAL'=>['Professional','badge-success'],'LEADER'=>['Leader','badge-accent']];
@endphp

{{-- ══ Định nghĩa hệ thống ══ --}}
<div class="mb-8">
    <div class="flex items-center gap-2 mb-3">
        <svg class="w-4 h-4 text-info" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3.055 11H5a2 2 0 012 2v1a2 2 0 002 2 2 2 0 012 2v2.945M8 3.935V5.5A2.5 2.5 0 0010.5 8h.5a2 2 0 012 2 2 2 0 104 0 2 2 0 012-2h1.064"/></svg>
        <h2 class="text-sm font-semibold">Định nghĩa hệ thống — dùng chung</h2>
        <span class="badge badge-info badge-xs">{{ $globalDefs->count() }}</span>
        @if(! $isSuperAdmin)
        <span class="text-xs text-base-content/30 ml-1">(Chỉ đọc)</span>
        @endif
    </div>

    @php $grouped = $globalDefs->groupBy('cert_type_code'); @endphp
    <div class="space-y-2">
        @forelse($grouped as $typeCode => $defs)
        <div class="card bg-base-100 shadow-sm border border-base-200">
            <div class="card-body p-4">
                <div class="flex items-center justify-between mb-2">
                    <p class="text-xs font-semibold text-base-content/50 uppercase tracking-wide">{{ $typeCode }}</p>
                    <span class="badge badge-info badge-outline badge-xs">Hệ thống</span>
                </div>
                <div class="flex flex-wrap gap-2">
                    @foreach($defs->sortBy('level_order') as $def)
                    @php [$lbl, $cls] = $levelBadge[$def->level_code] ?? [$def->level_code, 'badge-ghost']; @endphp
                    <div class="border border-base-200 rounded-lg p-3 text-xs {{ $def->is_active ? '' : 'opacity-50' }}">
                        <div class="flex items-center gap-1.5 mb-1">
                            <span class="badge {{ $cls }} badge-xs">{{ $lbl }}</span>
                            @if(! $def->is_active)<span class="badge badge-error badge-xs">Tắt</span>@endif
                        </div>
                        <p class="font-medium text-xs leading-tight mb-1">{{ $def->name }}</p>
                        <p class="text-base-content/40 font-mono text-xs">{{ $def->cert_code }}</p>
                        <div class="mt-1 text-base-content/40 space-y-0.5">
                            @if($def->min_workforce_score)<p>TDWCF ≥ {{ $def->min_workforce_score }}</p>@endif
                            @if($def->min_kpi_achievement_pct)<p>KPI ≥ {{ $def->min_kpi_achievement_pct }}%</p>@endif
                            @if($def->min_sandbox_hours)<p>Sandbox ≥ {{ $def->min_sandbox_hours }}h</p>@endif
                            @if($def->requires_impact_score)<p>Yêu cầu Impact Score</p>@endif
                            @if($def->requires_portfolio_approval)<p>Yêu cầu Portfolio</p>@endif
                        </div>
                        @if($isSuperAdmin)
                        <div class="flex gap-1 mt-2">
                            <a href="{{ route('backend.certs-admin.def.edit', $def) }}" class="btn btn-ghost btn-xs">Sửa</a>
                            <form method="POST" action="{{ route('backend.certs-admin.def.destroy', $def) }}" onsubmit="return confirm('Xoá?')">
                                @csrf @method('DELETE')
                                <button class="btn btn-ghost btn-xs text-error">Xoá</button>
                            </form>
                        </div>
                        @endif
                    </div>
                    @endforeach
                </div>
            </div>
        </div>
        @empty
        <div class="rounded-lg border border-dashed border-base-300 text-center py-6">
            <p class="text-base-content/30 text-sm">Chưa có định nghĩa hệ thống.</p>
        </div>
        @endforelse
    </div>
</div>

{{-- ══ Định nghĩa của tổ chức ══ --}}
<div>
    <div class="flex items-center gap-2 mb-3">
        <svg class="w-4 h-4 text-base-content/50" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16"/></svg>
        <h2 class="text-sm font-semibold">Định nghĩa của tổ chức</h2>
        <span class="badge badge-ghost badge-xs">{{ $orgDefs->count() }}</span>
    </div>

    @php $orgGrouped = $orgDefs->groupBy('cert_type_code'); @endphp
    <div class="space-y-2">
        @forelse($orgGrouped as $typeCode => $defs)
        <div class="card bg-base-100 shadow-sm border border-base-200">
            <div class="card-body p-4">
                <div class="flex items-center justify-between mb-2">
                    <p class="text-xs font-semibold text-base-content/50 uppercase tracking-wide">{{ $typeCode }}</p>
                    <span class="badge badge-ghost badge-outline badge-xs">Riêng</span>
                </div>
                <div class="flex flex-wrap gap-2">
                    @foreach($defs->sortBy('level_order') as $def)
                    @php [$lbl, $cls] = $levelBadge[$def->level_code] ?? [$def->level_code, 'badge-ghost']; @endphp
                    <div class="border border-base-200 rounded-lg p-3 text-xs {{ $def->is_active ? '' : 'opacity-50' }}">
                        <div class="flex items-center gap-1.5 mb-1">
                            <span class="badge {{ $cls }} badge-xs">{{ $lbl }}</span>
                            @if(! $def->is_active)<span class="badge badge-error badge-xs">Tắt</span>@endif
                        </div>
                        <p class="font-medium text-xs leading-tight mb-1">{{ $def->name }}</p>
                        <p class="text-base-content/40 font-mono text-xs">{{ $def->cert_code }}</p>
                        <div class="mt-1 text-base-content/40 space-y-0.5">
                            @if($def->min_workforce_score)<p>TDWCF ≥ {{ $def->min_workforce_score }}</p>@endif
                            @if($def->min_kpi_achievement_pct)<p>KPI ≥ {{ $def->min_kpi_achievement_pct }}%</p>@endif
                            @if($def->min_sandbox_hours)<p>Sandbox ≥ {{ $def->min_sandbox_hours }}h</p>@endif
                            @if($def->requires_impact_score)<p>Yêu cầu Impact Score</p>@endif
                            @if($def->requires_portfolio_approval)<p>Yêu cầu Portfolio</p>@endif
                        </div>
                        <div class="flex gap-1 mt-2">
                            <a href="{{ route('backend.certs-admin.def.edit', $def) }}" class="btn btn-ghost btn-xs">Sửa</a>
                            <form method="POST" action="{{ route('backend.certs-admin.def.destroy', $def) }}" onsubmit="return confirm('Xoá?')">
                                @csrf @method('DELETE')
                                <button class="btn btn-ghost btn-xs text-error">Xoá</button>
                            </form>
                        </div>
                    </div>
                    @endforeach
                </div>
            </div>
        </div>
        @empty
        <div class="rounded-lg border border-dashed border-base-300 text-center py-8">
            <p class="text-base-content/30 text-sm mb-2">Tổ chức chưa có định nghĩa riêng.</p>
            <a href="{{ route('backend.certs-admin.def.create') }}" class="btn btn-primary btn-sm">Tạo định nghĩa đầu tiên</a>
        </div>
        @endforelse
    </div>
</div>

@endsection
