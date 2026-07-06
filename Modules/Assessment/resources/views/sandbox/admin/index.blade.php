@extends('layouts.backend')
@section('title', 'Quản lý AI Sandbox')

@section('content')

@if(session('success'))
<div class="alert alert-success mb-4 py-2 px-4 text-sm">{{ session('success') }}</div>
@endif
@if(session('error'))
<div class="alert alert-error mb-4 py-2 px-4 text-sm">{{ session('error') }}</div>
@endif

{{-- Header --}}
<div class="flex flex-wrap items-center justify-between gap-3 mb-6">
    <div>
        <h1 class="text-xl font-bold text-base-content">Quản lý AI Sandbox</h1>
        <p class="text-sm text-base-content/50 mt-0.5">Quản lý môi trường thực hành và nhiệm vụ cho nhân viên</p>
    </div>
    <a href="{{ route('backend.sandbox-admin.env.create') }}" class="btn btn-primary btn-sm gap-1.5">
        <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/></svg>
        Thêm môi trường
    </a>
</div>

{{-- Global stats (scoped to current org) --}}
<div class="grid grid-cols-2 sm:grid-cols-4 gap-3 mb-6">
    @foreach([
        ['Môi trường', $globalStats['envs'], 'text-primary'],
        ['Nhiệm vụ',   $globalStats['tasks'], 'text-info'],
        ['Phiên hoàn thành', $globalStats['sessions'], 'text-success'],
        ['Điểm trung bình',  $globalStats['avg'] ? number_format($globalStats['avg'], 1) : '—', 'text-warning'],
    ] as [$label, $val, $cls])
    <div class="stat bg-base-100 border border-base-200 rounded-xl py-3 px-4 shadow-sm">
        <div class="stat-title text-xs">{{ $label }}</div>
        <div class="stat-value text-2xl {{ $cls }}">{{ $val }}</div>
    </div>
    @endforeach
</div>

{{-- Hướng dẫn quy trình --}}
<div class="card bg-info/5 border border-info/20 shadow-sm mb-6">
    <div class="card-body py-4 px-5">
        <h3 class="font-semibold text-sm text-info mb-3">Quy trình thiết lập & sử dụng</h3>
        <div class="grid grid-cols-1 sm:grid-cols-4 gap-3">
            @foreach([
                ['1', 'Admin tạo Môi trường', 'Định nghĩa loại kỹ năng (Office, Data, Sales…) và cấp độ (Tier 1-3)', 'M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16'],
                ['2', 'Thêm Nhiệm vụ', 'Viết hướng dẫn, kết quả mong đợi, rubric chấm điểm, giới hạn thời gian', 'M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2'],
                ['3', 'Nhân viên thực hành', 'Vào AI Sandbox → chọn môi trường → bắt đầu nhiệm vụ → dùng AI ngoài hệ thống → nộp bài', 'M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z'],
                ['4', 'Nhận điểm & Đo đếm', 'Tự động chấm ngay sau nộp. Điểm cập nhật vào Hồ sơ Digital Twin của nhân viên', 'M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z'],
            ] as [$num, $title, $desc, $icon])
            <div class="flex gap-3">
                <div class="w-7 h-7 rounded-full bg-info text-info-content text-xs font-bold flex items-center justify-center shrink-0">{{ $num }}</div>
                <div>
                    <p class="text-xs font-semibold text-base-content">{{ $title }}</p>
                    <p class="text-xs text-base-content/50 mt-0.5 leading-relaxed">{{ $desc }}</p>
                </div>
            </div>
            @endforeach
        </div>
    </div>
</div>

@php
$typeLabels = [
    'office'     => ['Văn phòng', 'badge-primary'],
    'data'       => ['Dữ liệu', 'badge-info'],
    'sales'      => ['Kinh doanh', 'badge-success'],
    'hr'         => ['Nhân sự', 'badge-secondary'],
    'workflow'   => ['Quy trình', 'badge-warning'],
    'leadership' => ['Lãnh đạo', 'badge-accent'],
    'custom'     => ['Tuỳ chỉnh', 'badge-ghost'],
];
@endphp

{{-- ══ Môi trường hệ thống (dùng chung) ══ --}}
<div class="mb-8">
    <div class="flex items-center gap-2 mb-3">
        <svg class="w-4 h-4 text-info" fill="none" viewBox="0 0 24 24" stroke="currentColor">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3.055 11H5a2 2 0 012 2v1a2 2 0 002 2 2 2 0 012 2v2.945M8 3.935V5.5A2.5 2.5 0 0010.5 8h.5a2 2 0 012 2 2 2 0 104 0 2 2 0 012-2h1.064M15 20.488V18a2 2 0 012-2h3.064"/>
        </svg>
        <h2 class="text-sm font-semibold text-base-content">Môi trường hệ thống — dùng chung</h2>
        <span class="badge badge-info badge-xs">{{ $globalEnvs->count() }} môi trường</span>
        @if(! $isSuperAdmin)
        <span class="text-xs text-base-content/30 ml-1">(Chỉ đọc — super-admin mới chỉnh sửa)</span>
        @endif
    </div>

    <div class="space-y-2">
        @forelse($globalEnvs as $env)
        @php
            $stat = $sessionStats[$env->id] ?? null;
            $typeLabel = $typeLabels[$env->type] ?? ['Khác', 'badge-ghost'];
        @endphp
        <div class="card bg-base-100 shadow-sm border {{ $env->is_active ? 'border-base-200' : 'border-base-300 opacity-60' }}">
            <div class="card-body p-4">
                <div class="flex items-center gap-4 flex-wrap">
                    <div class="flex-1 min-w-0">
                        <div class="flex items-center gap-2 flex-wrap mb-1">
                            <h3 class="font-semibold text-sm">{{ $env->name }}</h3>
                            <span class="badge {{ $typeLabel[1] }} badge-xs">{{ $typeLabel[0] }}</span>
                            <span class="badge badge-ghost badge-xs">Tier {{ $env->tier }}</span>
                            <span class="badge badge-info badge-outline badge-xs">Hệ thống</span>
                            @if(! $env->is_active)
                            <span class="badge badge-error badge-xs">Tắt</span>
                            @endif
                            <span class="text-xs text-base-content/30 font-mono">{{ $env->env_code }}</span>
                        </div>
                        @if($env->description)
                        <p class="text-xs text-base-content/50 line-clamp-1">{{ $env->description }}</p>
                        @endif
                    </div>

                    <div class="flex items-center gap-5 text-center">
                        <div>
                            <p class="text-xs text-base-content/40">Nhiệm vụ</p>
                            <p class="font-bold text-sm">{{ $env->active_tasks_count }}<span class="text-base-content/30 font-normal">/{{ $env->tasks_count }}</span></p>
                        </div>
                        <div>
                            <p class="text-xs text-base-content/40">Phiên (org bạn)</p>
                            <p class="font-bold text-sm text-success">{{ $stat?->total ?? 0 }}</p>
                        </div>
                        <div>
                            <p class="text-xs text-base-content/40">Điểm TB</p>
                            <p class="font-bold text-sm">{{ $stat?->avg_score ? number_format($stat->avg_score, 1) : '—' }}</p>
                        </div>
                    </div>

                    <div class="flex items-center gap-2 shrink-0">
                        <a href="{{ route('backend.sandbox-admin.tasks', $env) }}" class="btn btn-sm btn-outline gap-1">
                            <svg class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2"/></svg>
                            Nhiệm vụ
                        </a>
                        @if($isSuperAdmin)
                        <a href="{{ route('backend.sandbox-admin.env.edit', $env) }}" class="btn btn-sm btn-ghost">Sửa</a>
                        @endif
                    </div>
                </div>
            </div>
        </div>
        @empty
        <div class="rounded-lg border border-dashed border-base-300 text-center py-6">
            <p class="text-base-content/30 text-sm">Chưa có môi trường hệ thống nào.</p>
            @if($isSuperAdmin)
            <a href="{{ route('backend.sandbox-admin.env.create') }}" class="btn btn-ghost btn-xs mt-2">Tạo template hệ thống</a>
            @endif
        </div>
        @endforelse
    </div>
</div>

{{-- ══ Môi trường riêng của tổ chức ══ --}}
<div>
    <div class="flex items-center gap-2 mb-3">
        <svg class="w-4 h-4 text-base-content/50" fill="none" viewBox="0 0 24 24" stroke="currentColor">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-2 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4"/>
        </svg>
        <h2 class="text-sm font-semibold text-base-content">Môi trường của tổ chức</h2>
        <span class="badge badge-ghost badge-xs">{{ $orgEnvs->count() }} môi trường</span>
    </div>

    <div class="space-y-2">
        @forelse($orgEnvs as $env)
        @php
            $stat = $sessionStats[$env->id] ?? null;
            $typeLabel = $typeLabels[$env->type] ?? ['Khác', 'badge-ghost'];
        @endphp
        <div class="card bg-base-100 shadow-sm border {{ $env->is_active ? 'border-base-200' : 'border-base-300 opacity-60' }}">
            <div class="card-body p-4">
                <div class="flex items-center gap-4 flex-wrap">
                    <div class="flex-1 min-w-0">
                        <div class="flex items-center gap-2 flex-wrap mb-1">
                            <h3 class="font-semibold text-sm">{{ $env->name }}</h3>
                            <span class="badge {{ $typeLabel[1] }} badge-xs">{{ $typeLabel[0] }}</span>
                            <span class="badge badge-ghost badge-xs">Tier {{ $env->tier }}</span>
                            <span class="badge badge-ghost badge-outline badge-xs">Riêng</span>
                            @if(! $env->is_active)
                            <span class="badge badge-error badge-xs">Tắt</span>
                            @endif
                            <span class="text-xs text-base-content/30 font-mono">{{ $env->env_code }}</span>
                        </div>
                        @if($env->description)
                        <p class="text-xs text-base-content/50 line-clamp-1">{{ $env->description }}</p>
                        @endif
                    </div>

                    <div class="flex items-center gap-5 text-center">
                        <div>
                            <p class="text-xs text-base-content/40">Nhiệm vụ</p>
                            <p class="font-bold text-sm">{{ $env->active_tasks_count }}<span class="text-base-content/30 font-normal">/{{ $env->tasks_count }}</span></p>
                        </div>
                        <div>
                            <p class="text-xs text-base-content/40">Phiên hoàn thành</p>
                            <p class="font-bold text-sm text-success">{{ $stat?->total ?? 0 }}</p>
                        </div>
                        <div>
                            <p class="text-xs text-base-content/40">Điểm TB</p>
                            <p class="font-bold text-sm">{{ $stat?->avg_score ? number_format($stat->avg_score, 1) : '—' }}</p>
                        </div>
                    </div>

                    <div class="flex items-center gap-2 shrink-0">
                        <a href="{{ route('backend.sandbox-admin.tasks', $env) }}" class="btn btn-sm btn-outline gap-1">
                            <svg class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2"/></svg>
                            Nhiệm vụ
                        </a>
                        <a href="{{ route('backend.sandbox-admin.env.edit', $env) }}" class="btn btn-sm btn-ghost">Sửa</a>
                    </div>
                </div>
            </div>
        </div>
        @empty
        <div class="rounded-lg border border-dashed border-base-300 text-center py-8">
            <p class="text-base-content/30 text-sm mb-2">Tổ chức chưa có môi trường riêng nào.</p>
            <p class="text-xs text-base-content/20 mb-3">Tạo môi trường riêng để thiết kế bài tập phù hợp với đặc thù nghiệp vụ của tổ chức bạn.</p>
            <a href="{{ route('backend.sandbox-admin.env.create') }}" class="btn btn-primary btn-sm">Tạo môi trường đầu tiên</a>
        </div>
        @endforelse
    </div>
</div>

@endsection
