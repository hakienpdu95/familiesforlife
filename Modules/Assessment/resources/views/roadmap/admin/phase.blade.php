@extends('layouts.backend')
@section('title', 'Phase — ' . $phase->title)

@section('content')

{{-- Breadcrumb --}}
<div class="text-xs breadcrumbs mb-4 text-base-content/40">
    <ul>
        <li><a href="{{ route('backend.roadmap-admin.index') }}">Roadmap Admin</a></li>
        <li>{{ $phase->title }}</li>
    </ul>
</div>

<div class="flex items-center justify-between mb-5">
    <div>
        <div class="flex items-center gap-2">
            <span class="badge badge-ghost badge-sm">{{ $phase->maturity_level }}</span>
            <span class="font-mono text-xs text-base-content/40">{{ $phase->phase_code }}</span>
        </div>
        <h1 class="text-xl font-bold mt-1">{{ $phase->title }}</h1>
        @if($phase->description)
        <p class="text-sm text-base-content/50 mt-0.5">{{ $phase->description }}</p>
        @endif
    </div>
    @if($phase->duration_weeks)
    <div class="stat bg-base-100 border border-base-200 rounded-xl py-2 px-4 shadow-sm">
        <div class="stat-title text-xs">Thời lượng</div>
        <div class="stat-value text-lg">{{ $phase->duration_weeks }}</div>
        <div class="stat-desc text-xs">tuần</div>
    </div>
    @endif
</div>

@if(session('flash_success'))
<div class="alert alert-success mb-4 py-2 px-4 text-sm">{{ session('flash_success') }}</div>
@endif

@if($phase->milestones->isEmpty())
<div class="card bg-base-100 border border-base-200 shadow-sm">
    <div class="card-body items-center text-center py-10">
        <p class="text-base-content/40 text-sm">Phase này chưa có milestone nào.</p>
    </div>
</div>
@else
<div class="space-y-3">
    @foreach($phase->milestones as $milestone)
    <div class="card bg-base-100 border border-base-200 shadow-sm hover:border-primary/40 transition-colors">
        <div class="card-body p-4">
            <div class="flex items-center justify-between gap-3">
                <div class="flex items-center gap-3 min-w-0">
                    <div class="w-8 h-8 rounded-full bg-primary/10 text-primary flex items-center justify-center shrink-0 font-semibold text-sm">
                        {{ $milestone->sort_order + 1 }}
                    </div>
                    <div class="min-w-0">
                        <p class="font-medium text-sm leading-snug">{{ $milestone->title }}</p>
                        @if($milestone->description)
                        <p class="text-xs text-base-content/50 mt-0.5 truncate">{{ $milestone->description }}</p>
                        @endif
                    </div>
                </div>
                <div class="flex items-center gap-2 shrink-0">
                    <span class="badge badge-ghost badge-sm">
                        {{ $milestone->kc_items_count }} tài liệu
                    </span>
                    <a href="{{ route('backend.roadmap-admin.milestone.kc', [$phase, $milestone]) }}"
                       class="btn btn-sm btn-outline btn-primary">
                        Quản lý KC
                    </a>
                </div>
            </div>
        </div>
    </div>
    @endforeach
</div>
@endif

@endsection
