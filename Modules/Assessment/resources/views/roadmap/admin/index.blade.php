@extends('layouts.backend')
@section('title', 'Roadmap Admin — Lộ trình học tập')

@section('content')

<div class="flex items-center justify-between mb-5">
    <div>
        <h1 class="text-2xl font-bold">Roadmap — Lộ trình TDWCF</h1>
        <p class="text-sm text-base-content/50 mt-0.5">Quản lý phases, milestones và tài liệu KC đính kèm</p>
    </div>
    <a href="{{ route('backend.career-pathway-admin.index') }}" class="btn btn-ghost btn-sm">
        Bước lộ trình →
    </a>
</div>

@if(session('flash_success'))
<div class="alert alert-success mb-4 py-2 px-4 text-sm">{{ session('flash_success') }}</div>
@endif

@if($phases->isEmpty())
<div class="card bg-base-100 border border-base-200 shadow-sm">
    <div class="card-body items-center text-center py-12">
        <p class="text-base-content/40 text-sm">Chưa có phase nào trong hệ thống.</p>
        <p class="text-xs text-base-content/30 mt-1">Seed dữ liệu qua <code>php artisan db:seed</code></p>
    </div>
</div>
@else
<div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4">
    @foreach($phases as $phase)
    @php
        $levelColors = [
            'DIGITAL_BEGINNER'     => 'border-base-300 text-base-content/50',
            'DIGITAL_AWARE'        => 'border-info/40 text-info',
            'DIGITAL_PRACTITIONER' => 'border-warning/40 text-warning',
            'DIGITAL_PROFESSIONAL' => 'border-success/40 text-success',
            'DIGITAL_LEADER'       => 'border-accent/40 text-accent',
        ];
        $cls = $levelColors[$phase->maturity_level] ?? 'border-base-200 text-base-content/50';
    @endphp
    <div class="card bg-base-100 border {{ $cls }} shadow-sm hover:shadow-md transition-shadow">
        <div class="card-body p-4">
            <div class="flex items-start justify-between gap-2">
                <div>
                    <span class="text-xs font-mono text-base-content/40">{{ $phase->phase_code }}</span>
                    <h3 class="font-semibold text-sm mt-0.5 leading-snug">{{ $phase->title }}</h3>
                </div>
                <span class="badge badge-ghost badge-xs shrink-0">{{ $phase->maturity_level }}</span>
            </div>
            <div class="flex items-center gap-3 mt-3 text-xs text-base-content/50">
                <span>{{ $phase->milestones_count }} milestones</span>
                @if($phase->duration_weeks)
                <span>~{{ $phase->duration_weeks }} tuần</span>
                @endif
            </div>
            <div class="mt-3">
                <a href="{{ route('backend.roadmap-admin.phase', $phase) }}"
                   class="btn btn-sm btn-outline w-full">Quản lý milestones</a>
            </div>
        </div>
    </div>
    @endforeach
</div>
@endif

@endsection
