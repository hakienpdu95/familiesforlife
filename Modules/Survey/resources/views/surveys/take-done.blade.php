@extends('layouts.backend')
@section('title', 'Đã hoàn thành khảo sát')

@section('content')

{{-- Page header --}}
<div class="flex items-start justify-between mb-6">
    <div>
        <h1 class="text-2xl font-bold text-base-content">{{ $survey->title }}</h1>
        <p class="text-sm text-base-content/50 mt-0.5">Kết quả khảo sát</p>
    </div>
    <a href="{{ route('backend.dashboard') }}" class="btn btn-ghost btn-sm gap-1.5 shrink-0 ml-4">
        <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                  d="M3 12l2-2m0 0l7-7 7 7M5 10v10a1 1 0 001 1h3m10-11l2 2m-2-2v10a1 1 0 01-1 1h-3m-6 0a1 1 0 001-1v-4a1 1 0 011-1h2a1 1 0 011 1v4a1 1 0 001 1m-6 0h6"/>
        </svg>
        Về trang chủ
    </a>
</div>

<div class="grid grid-cols-1 xl:grid-cols-[1fr_320px] gap-5 items-start">

    {{-- Main completion card --}}
    <div class="card bg-base-100 shadow-sm border border-base-200">
        <div class="card-body items-center text-center py-12">

            {{-- Success icon --}}
            <div class="w-20 h-20 rounded-full bg-success/10 flex items-center justify-center mb-5">
                <svg class="w-10 h-10 text-success" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/>
                </svg>
            </div>

            <h2 class="text-xl font-bold text-base-content mb-2">Cảm ơn bạn!</h2>
            <p class="text-sm text-base-content/50">
                Bạn đã hoàn thành khảo sát
                <span class="font-medium text-base-content">{{ $survey->title }}</span>.
            </p>

            @if($survey->allow_multiple_responses)
            <div class="mt-6">
                <a href="{{ route('backend.surveys.take', $survey->slug) }}" class="btn btn-outline btn-sm">
                    Làm lại khảo sát
                </a>
            </div>
            @endif

        </div>
    </div>

    {{-- Score / result sidebar --}}
    @if($response && $response->result)
    @php $result = $response->result; @endphp
    <div class="space-y-4">

        {{-- Score card --}}
        <div class="card bg-base-100 shadow-sm border border-base-200">
            <div class="card-body p-4">

                <p class="text-xs font-semibold text-base-content/40 uppercase tracking-wide mb-4">
                    Kết quả của bạn
                </p>

                @if($result->total_score !== null)
                <div class="flex items-end gap-2 mb-3">
                    <span class="text-4xl font-bold text-primary tabular-nums">
                        {{ number_format($result->total_score, 1) }}
                    </span>
                    @if($result->max_score)
                    <span class="text-base-content/40 text-sm mb-1">
                        / {{ number_format($result->max_score, 1) }}
                    </span>
                    @endif
                </div>

                @if($result->max_score && $result->max_score > 0)
                @php $pct = min(100, round(($result->total_score / $result->max_score) * 100)); @endphp
                <div class="mb-3">
                    <progress class="progress progress-primary w-full h-2" value="{{ $pct }}" max="100"></progress>
                    <p class="text-xs text-base-content/40 text-right mt-0.5">{{ $pct }}%</p>
                </div>
                @endif
                @endif

                @if($result->band_label)
                @php
                    $bandPct = isset($pct) ? $pct : null;
                    $badgeColor = $bandPct === null ? 'badge-primary'
                        : ($bandPct >= 80 ? 'badge-success' : ($bandPct >= 50 ? 'badge-warning' : 'badge-error'));
                @endphp
                <div class="mb-3">
                    <span class="badge {{ $badgeColor }} badge-lg">{{ $result->band_label }}</span>
                </div>
                @endif

                @if($result->feedback)
                <p class="text-sm text-base-content/70 border-t border-base-200 pt-3 mt-1 leading-relaxed">
                    {{ $result->feedback }}
                </p>
                @endif

            </div>
        </div>

    </div>
    @endif

</div>

@endsection
