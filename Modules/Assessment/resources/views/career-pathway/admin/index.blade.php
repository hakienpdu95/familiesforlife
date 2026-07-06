@extends('layouts.backend')
@section('title', 'Quản lý Lộ trình nghề nghiệp')

@section('content')

@if(session('success'))
<div class="alert alert-success mb-4 py-2 px-4 text-sm">{{ session('success') }}</div>
@endif
@if(session('error'))
<div class="alert alert-error mb-4 py-2 px-4 text-sm">{{ session('error') }}</div>
@endif

<div class="flex flex-wrap items-center justify-between gap-3 mb-6">
    <div>
        <h1 class="text-xl font-bold">Quản lý Lộ trình nghề nghiệp</h1>
        <p class="text-sm text-base-content/50 mt-0.5">Thiết kế các bước thăng tiến theo chuẩn TDWCF</p>
    </div>
    <div class="flex gap-2">
        <a href="{{ route('backend.career-pathway.index') }}" class="btn btn-ghost btn-sm gap-1.5">
            <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0zM2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"/></svg>
            Xem lộ trình
        </a>
        <a href="{{ route('backend.career-pathway-admin.create') }}" class="btn btn-primary btn-sm gap-1.5">
            <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/></svg>
            Thêm bước
        </a>
    </div>
</div>

@php
$levelLabels = [
    'DIGITAL_BEGINNER'     => ['Khởi đầu',     'badge-ghost'],
    'DIGITAL_AWARE'        => ['Nhận thức',    'badge-info'],
    'DIGITAL_PRACTITIONER' => ['Thực hành',    'badge-warning'],
    'DIGITAL_PROFESSIONAL' => ['Chuyên nghiệp','badge-success'],
    'DIGITAL_LEADER'       => ['Dẫn dắt',      'badge-accent'],
];
@endphp

{{-- ══ Bước hệ thống (dùng chung) ══ --}}
<div class="mb-8">
    <div class="flex items-center gap-2 mb-3">
        <svg class="w-4 h-4 text-info" fill="none" viewBox="0 0 24 24" stroke="currentColor">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3.055 11H5a2 2 0 012 2v1a2 2 0 002 2 2 2 0 012 2v2.945M8 3.935V5.5A2.5 2.5 0 0010.5 8h.5a2 2 0 012 2 2 2 0 104 0 2 2 0 012-2h1.064M15 20.488V18a2 2 0 012-2h3.064"/>
        </svg>
        <h2 class="text-sm font-semibold">Bước hệ thống — dùng chung</h2>
        <span class="badge badge-info badge-xs">{{ $globalSteps->count() }} bước</span>
        @if(! $isSuperAdmin)
        <span class="text-xs text-base-content/30 ml-1">(Chỉ đọc — super-admin mới chỉnh sửa)</span>
        @endif
    </div>

    <div class="space-y-2">
        @forelse($globalSteps as $step)
        @php [$lbl, $cls] = $levelLabels[$step->from_level] ?? ['—','badge-ghost']; @endphp
        <div class="card bg-base-100 shadow-sm border border-base-200 {{ $step->is_active ? '' : 'opacity-60' }}">
            <div class="card-body p-4">
                <div class="flex items-center gap-4 flex-wrap">
                    <div class="w-7 h-7 rounded-full bg-base-200 text-xs font-bold flex items-center justify-center shrink-0">
                        {{ $step->step_order }}
                    </div>
                    <div class="flex-1 min-w-0">
                        <div class="flex items-center gap-2 flex-wrap mb-1">
                            <h3 class="font-semibold text-sm">{{ $step->title }}</h3>
                            <span class="badge {{ $cls }} badge-xs">{{ $lbl }}</span>
                            @if($step->from_level !== $step->to_level)
                            <svg class="w-3 h-3 text-base-content/30" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/></svg>
                            <span class="badge {{ $levelLabels[$step->to_level][1] ?? 'badge-ghost' }} badge-xs">{{ $levelLabels[$step->to_level][0] ?? $step->to_level }}</span>
                            @endif
                            <span class="badge badge-info badge-outline badge-xs">Hệ thống</span>
                            @if(! $step->is_active)<span class="badge badge-error badge-xs">Tắt</span>@endif
                        </div>
                        <div class="flex flex-wrap gap-3 text-xs text-base-content/40">
                            @if($step->estimated_weeks)<span>~{{ $step->estimated_weeks }} tuần</span>@endif
                            @if($step->required_cert_code)<span class="text-warning">Cert: {{ $step->required_cert_code }}</span>@endif
                            @if($step->recommended_sandbox_env_code)<span class="text-info">Sandbox: {{ $step->recommended_sandbox_env_code }}</span>@endif
                        </div>
                    </div>
                    @if($isSuperAdmin)
                    <div class="flex gap-2 shrink-0">
                        <a href="{{ route('backend.career-pathway-admin.edit', $step) }}" class="btn btn-ghost btn-sm">Sửa</a>
                        <form method="POST" action="{{ route('backend.career-pathway-admin.destroy', $step) }}"
                              onsubmit="return confirm('Xoá bước này?')">
                            @csrf @method('DELETE')
                            <button class="btn btn-ghost btn-sm text-error">Xoá</button>
                        </form>
                    </div>
                    @endif
                </div>
            </div>
        </div>
        @empty
        <div class="rounded-lg border border-dashed border-base-300 text-center py-6">
            <p class="text-base-content/30 text-sm">Chưa có bước hệ thống nào.</p>
        </div>
        @endforelse
    </div>
</div>

{{-- ══ Bước riêng của tổ chức ══ --}}
<div>
    <div class="flex items-center gap-2 mb-3">
        <svg class="w-4 h-4 text-base-content/50" fill="none" viewBox="0 0 24 24" stroke="currentColor">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5"/>
        </svg>
        <h2 class="text-sm font-semibold">Bước riêng của tổ chức</h2>
        <span class="badge badge-ghost badge-xs">{{ $orgSteps->count() }} bước</span>
    </div>

    <div class="space-y-2">
        @forelse($orgSteps as $step)
        @php [$lbl, $cls] = $levelLabels[$step->from_level] ?? ['—','badge-ghost']; @endphp
        <div class="card bg-base-100 shadow-sm border border-base-200 {{ $step->is_active ? '' : 'opacity-60' }}">
            <div class="card-body p-4">
                <div class="flex items-center gap-4 flex-wrap">
                    <div class="w-7 h-7 rounded-full bg-base-200 text-xs font-bold flex items-center justify-center shrink-0">
                        {{ $step->step_order }}
                    </div>
                    <div class="flex-1 min-w-0">
                        <div class="flex items-center gap-2 flex-wrap mb-1">
                            <h3 class="font-semibold text-sm">{{ $step->title }}</h3>
                            <span class="badge {{ $cls }} badge-xs">{{ $lbl }}</span>
                            @if($step->from_level !== $step->to_level)
                            <svg class="w-3 h-3 text-base-content/30" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/></svg>
                            <span class="badge {{ $levelLabels[$step->to_level][1] ?? 'badge-ghost' }} badge-xs">{{ $levelLabels[$step->to_level][0] ?? $step->to_level }}</span>
                            @endif
                            <span class="badge badge-ghost badge-outline badge-xs">Riêng</span>
                            @if(! $step->is_active)<span class="badge badge-error badge-xs">Tắt</span>@endif
                        </div>
                        <div class="flex flex-wrap gap-3 text-xs text-base-content/40">
                            @if($step->estimated_weeks)<span>~{{ $step->estimated_weeks }} tuần</span>@endif
                            @if($step->required_cert_code)<span class="text-warning">Cert: {{ $step->required_cert_code }}</span>@endif
                            @if($step->recommended_sandbox_env_code)<span class="text-info">Sandbox: {{ $step->recommended_sandbox_env_code }}</span>@endif
                        </div>
                    </div>
                    <div class="flex gap-2 shrink-0">
                        <a href="{{ route('backend.career-pathway-admin.edit', $step) }}" class="btn btn-ghost btn-sm">Sửa</a>
                        <form method="POST" action="{{ route('backend.career-pathway-admin.destroy', $step) }}"
                              onsubmit="return confirm('Xoá bước này?')">
                            @csrf @method('DELETE')
                            <button class="btn btn-ghost btn-sm text-error">Xoá</button>
                        </form>
                    </div>
                </div>
            </div>
        </div>
        @empty
        <div class="rounded-lg border border-dashed border-base-300 text-center py-8">
            <p class="text-base-content/30 text-sm mb-2">Tổ chức chưa có bước riêng nào.</p>
            <p class="text-xs text-base-content/20 mb-3">Tạo bước riêng để tuỳ chỉnh lộ trình theo đặc thù nghiệp vụ của tổ chức.</p>
            <a href="{{ route('backend.career-pathway-admin.create') }}" class="btn btn-primary btn-sm">Thêm bước đầu tiên</a>
        </div>
        @endforelse
    </div>
</div>

@endsection
