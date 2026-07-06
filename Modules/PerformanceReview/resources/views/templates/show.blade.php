@extends('layouts.backend')
@section('title', 'Chi tiết mẫu — ' . $template->name)


@section('content')

<div class="flex items-center justify-between mb-5">
    <div>
        <h1 class="text-2xl font-bold text-base-content">{{ $template->name }}</h1>
        <p class="text-sm text-base-content/50 mt-0.5">Thang điểm {{ $template->rating_scale }} · {{ $template->period_type->label() }}</p>
    </div>
    <a href="{{ route('backend.review-templates.index') }}" class="btn btn-ghost btn-sm">Quay lại</a>
</div>

<div class="card bg-base-100 shadow-sm border border-base-200 max-w-2xl">
    <div class="card-body p-5">
        <div class="flex items-center gap-2 mb-4">
            @if($template->is_system)
            <span class="badge badge-ghost badge-sm">Hệ thống</span>
            @endif
            @if($template->is_locked)
            <span class="badge badge-warning badge-sm">Đã khóa</span>
            @endif
            @if(!$template->is_active)
            <span class="badge badge-error badge-sm">Không hoạt động</span>
            @else
            <span class="badge badge-success badge-sm">Hoạt động</span>
            @endif
        </div>

        <h2 class="text-sm font-semibold text-base-content/60 uppercase tracking-wide mb-3">Tiêu chí đánh giá</h2>

        @php $totalWeight = $template->criteria->sum('weight'); @endphp

        <div class="space-y-3">
            @foreach($template->criteria as $c)
            <div class="border border-base-200 rounded-xl p-4">
                <div class="flex items-center justify-between mb-1">
                    <span class="font-semibold text-sm">{{ $c->criteria_name }}</span>
                    <span class="text-xs font-mono text-base-content/40">{{ $c->criteria_key }}</span>
                </div>
                <div class="flex items-center gap-4 text-xs text-base-content/50">
                    <span>Trọng số: <strong>{{ $c->weight }}%</strong></span>
                    <span>Điểm tối đa: <strong>{{ $c->max_score }}</strong></span>
                </div>
                @if($c->description)
                <p class="text-xs text-base-content/50 mt-1.5 italic">{{ $c->description }}</p>
                @endif
            </div>
            @endforeach
        </div>

        <div class="border-t border-base-200 pt-3 mt-3 flex justify-between text-sm">
            <span class="text-base-content/50">Tổng trọng số</span>
            <span class="font-bold {{ abs($totalWeight - 100) < 0.01 ? 'text-success' : 'text-error' }}">
                {{ number_format($totalWeight, 1) }}%
                @if(abs($totalWeight - 100) >= 0.01)
                <span class="text-xs">(phải = 100%)</span>
                @endif
            </span>
        </div>
    </div>
</div>
@endsection
