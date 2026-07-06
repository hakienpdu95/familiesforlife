@extends('layouts.backend')
@section('title', 'KC — ' . $milestone->title)

@section('content')

{{-- Breadcrumb --}}
<div class="text-xs breadcrumbs mb-4 text-base-content/40">
    <ul>
        <li><a href="{{ route('backend.roadmap-admin.index') }}">Roadmap Admin</a></li>
        <li><a href="{{ route('backend.roadmap-admin.phase', $phase) }}">{{ $phase->title }}</a></li>
        <li>{{ $milestone->title }}</li>
    </ul>
</div>

@php
    $difficultyLabels = [1 => 'Cơ bản', 2 => 'Trung cấp', 3 => 'Nâng cao'];
    $difficultyBadge  = [1 => 'badge-info', 2 => 'badge-warning', 3 => 'badge-error'];
    $typeLabels = ['document'=>'Tài liệu','sop'=>'SOP','video'=>'Video','form'=>'Biểu mẫu','faq'=>'FAQ','case_study'=>'Case Study','policy'=>'Chính sách'];
@endphp

<div class="flex items-center justify-between mb-5">
    <div>
        <div class="flex items-center gap-2">
            <span class="badge badge-ghost badge-sm">{{ $phase->maturity_level }}</span>
            <span class="text-xs text-base-content/40">{{ $phase->title }}</span>
        </div>
        <h1 class="text-xl font-bold mt-1">{{ $milestone->title }}</h1>
        @if($milestone->description)
        <p class="text-sm text-base-content/50 mt-0.5">{{ $milestone->description }}</p>
        @endif
    </div>
</div>

@if(session('flash_success'))
<div class="alert alert-success mb-4 py-2 px-4 text-sm">{{ session('flash_success') }}</div>
@endif

<div class="grid grid-cols-1 lg:grid-cols-5 gap-5">

    {{-- ── Danh sách KC đã gắn ────────────────────────────────────────────────── --}}
    <div class="lg:col-span-3">
        <h2 class="text-sm font-semibold text-base-content/60 mb-3 uppercase tracking-wide">
            Tài liệu đã gắn ({{ $milestone->kcItems->count() }})
        </h2>

        @if($milestone->kcItems->isEmpty())
        <div class="card bg-base-100 border border-dashed border-base-300 shadow-sm">
            <div class="card-body items-center text-center py-10">
                <svg class="w-8 h-8 text-base-content/20 mb-2" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M12 6.253v13m0-13C10.832 5.477 9.246 5 7.5 5S4.168 5.477 3 6.253v13C4.168 18.477 5.754 18 7.5 18s3.332.477 4.5 1.253m0-13C13.168 5.477 14.754 5 16.5 5c1.747 0 3.332.477 4.5 1.253v13C19.832 18.477 18.247 18 16.5 18c-1.746 0-3.332.477-4.5 1.253"/>
                </svg>
                <p class="text-sm text-base-content/40">Chưa có tài liệu nào. Gắn KC từ danh sách bên phải.</p>
            </div>
        </div>
        @else
        <div class="space-y-2">
            @foreach($milestone->kcItems as $kc)
            <div class="card bg-base-100 border border-base-200 shadow-sm">
                <div class="card-body p-3">
                    <div class="flex items-start justify-between gap-2">
                        <div class="flex-1 min-w-0">
                            <p class="text-sm font-medium leading-snug truncate">{{ $kc->title }}</p>
                            <div class="flex items-center gap-1.5 mt-1 flex-wrap">
                                <span class="badge badge-ghost badge-xs">{{ $typeLabels[$kc->type?->value ?? $kc->type] ?? $kc->type }}</span>
                                @if($kc->domain_code)
                                <span class="badge badge-primary badge-xs">{{ $kc->domain_code }}</span>
                                @endif
                                @if($kc->difficulty)
                                <span class="badge {{ $difficultyBadge[$kc->difficulty] ?? 'badge-ghost' }} badge-xs">
                                    {{ $difficultyLabels[$kc->difficulty] ?? $kc->difficulty }}
                                </span>
                                @endif
                                <span class="text-xs text-base-content/30 font-mono">#{{ $kc->pivot->sort_order }}</span>
                            </div>
                        </div>
                        <form method="POST"
                              action="{{ route('backend.roadmap-admin.milestone.kc.detach', [$phase, $milestone, $kc]) }}"
                              onsubmit="return confirm('Gỡ tài liệu này?')">
                            @csrf
                            @method('DELETE')
                            <button type="submit" class="btn btn-ghost btn-xs text-error hover:bg-error/10">
                                <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                                </svg>
                            </button>
                        </form>
                    </div>
                </div>
            </div>
            @endforeach
        </div>
        @endif
    </div>

    {{-- ── Gắn KC mới ─────────────────────────────────────────────────────────── --}}
    <div class="lg:col-span-2">
        <h2 class="text-sm font-semibold text-base-content/60 mb-3 uppercase tracking-wide">
            Gắn tài liệu KC
        </h2>
        <div class="card bg-base-100 border border-base-200 shadow-sm">
            <div class="card-body p-4" x-data="{ search: '' }">

                <form method="POST"
                      action="{{ route('backend.roadmap-admin.milestone.kc.attach', [$phase, $milestone]) }}">
                    @csrf

                    <div class="form-control mb-3">
                        <input type="text" x-model="search"
                               class="input input-bordered input-sm w-full"
                               placeholder="Tìm tài liệu...">
                    </div>

                    @if($availableKc->isEmpty())
                    <p class="text-xs text-base-content/40 text-center py-4">
                        Tất cả tài liệu approved đã được gắn.
                    </p>
                    @else
                    <div class="overflow-y-auto max-h-72 space-y-1 mb-3">
                        @foreach($availableKc as $kc)
                        <label class="flex items-start gap-2.5 p-2 rounded-lg hover:bg-base-200 cursor-pointer transition-colors"
                               x-show="search === '' || '{{ strtolower($kc->title) }}'.includes(search.toLowerCase())">
                            <input type="radio" name="kc_item_id" value="{{ $kc->id }}"
                                   class="radio radio-sm radio-primary mt-0.5 shrink-0" required>
                            <div class="min-w-0">
                                <p class="text-sm leading-snug">{{ $kc->title }}</p>
                                <div class="flex gap-1 mt-0.5 flex-wrap">
                                    <span class="badge badge-ghost badge-xs">{{ $typeLabels[$kc->type?->value ?? $kc->type] ?? $kc->type }}</span>
                                    @if($kc->domain_code)
                                    <span class="badge badge-primary badge-xs">{{ $kc->domain_code }}</span>
                                    @endif
                                    @if($kc->difficulty)
                                    <span class="badge {{ $difficultyBadge[$kc->difficulty] ?? 'badge-ghost' }} badge-xs">
                                        {{ $difficultyLabels[$kc->difficulty] ?? '' }}
                                    </span>
                                    @endif
                                </div>
                            </div>
                        </label>
                        @endforeach
                    </div>

                    <button type="submit" class="btn btn-primary btn-sm w-full">
                        <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/>
                        </svg>
                        Gắn tài liệu đã chọn
                    </button>
                    @endif

                </form>
            </div>
        </div>
    </div>

</div>

@endsection
