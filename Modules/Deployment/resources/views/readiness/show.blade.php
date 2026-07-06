@extends('layouts.backend')
@section('title', 'Kết quả Readiness — ' . ($target->targetOrganization?->name ?? 'Target'))

@section('content')
<div class="max-w-3xl space-y-5">

    <div class="flex items-center justify-between">
        <div>
            <h1 class="text-xl font-bold">Kết quả đánh giá sẵn sàng</h1>
            <p class="text-sm text-base-content/50 mt-0.5">{{ $target->targetOrganization?->name ?? '—' }}</p>
        </div>
        <div class="flex gap-2">
            <a href="{{ route('deployment.readiness.fill', ['vertical' => $vertical->code(), 'target' => $target->id]) }}"
               class="btn btn-ghost btn-sm">Làm lại</a>
            <a href="{{ route('deployment.targets.show', ['vertical' => $vertical->code(), 'target' => $target->id]) }}"
               class="btn btn-outline btn-sm">← Về target</a>
        </div>
    </div>

    @if(session('success'))
    <div class="alert alert-success">{{ session('success') }}</div>
    @endif

    @if(! $result)
    <div class="card bg-base-100 border border-base-200 p-8 text-center">
        <p class="text-base-content/50 mb-4">Chưa có dữ liệu đánh giá.</p>
        <a href="{{ route('deployment.readiness.fill', ['vertical' => $vertical->code(), 'target' => $target->id]) }}"
           class="btn btn-primary btn-sm mx-auto">Bắt đầu đánh giá</a>
    </div>
    @else

    {{-- Score hero card --}}
    <div class="card bg-base-100 border border-base-200 shadow-sm">
        <div class="card-body items-center text-center py-8">
            <div class="radial-progress text-{{ $result['color'] }} border-{{ $result['color'] }} border-4"
                 style="--value:{{ $result['score'] }}; --size:7rem; --thickness:8px;"
                 role="progressbar">
                <span class="text-2xl font-bold">{{ $result['score'] }}</span>
            </div>
            <h2 class="text-lg font-bold mt-3">{{ $result['band'] }}</h2>
            <p class="text-sm text-base-content/50">
                Readiness: {{ $result['score'] }}/100 · {{ $result['answered'] }} câu đã trả lời
            </p>
        </div>
    </div>

    {{-- Domain scores --}}
    @if(! empty($result['domains']))
    <div class="card bg-base-100 border border-base-200 shadow-sm">
        <div class="card-body">
            <h3 class="font-semibold text-sm mb-4">Điểm theo domain</h3>
            @php
                $domainLabels = [
                    // readiness_v1 domains
                    'legal'   => 'Pháp lý & Giấy tờ',
                    'hr'      => 'Nhân sự & Năng lực',
                    'infra'   => 'Hạ tầng & Công nghệ',
                    'process' => 'Quy trình & Dữ liệu',
                    // TXNG readiness domains
                    'infrastructure'  => 'Hạ tầng kỹ thuật',
                    'personnel'       => 'Nhân sự & năng lực',
                    'data_readiness'  => 'Dữ liệu hiện có',
                ];
            @endphp
            <div class="space-y-3">
                @foreach($result['domains'] as $code => $domain)
                <div>
                    <div class="flex justify-between text-sm mb-1">
                        <span>{{ $domainLabels[$code] ?? $code }}</span>
                        <span class="font-semibold
                            {{ $domain['score'] >= 80 ? 'text-success' : ($domain['score'] >= 60 ? 'text-info' : ($domain['score'] >= 40 ? 'text-warning' : 'text-error')) }}">
                            {{ $domain['score'] }}%
                        </span>
                    </div>
                    <div class="w-full bg-base-200 rounded-full h-2 overflow-hidden">
                        <div class="h-2 rounded-full transition-all
                            {{ $domain['score'] >= 80 ? 'bg-success' : ($domain['score'] >= 60 ? 'bg-info' : ($domain['score'] >= 40 ? 'bg-warning' : 'bg-error')) }}"
                             style="width: {{ $domain['score'] }}%"></div>
                    </div>
                </div>
                @endforeach
            </div>
        </div>
    </div>
    @endif

    {{-- Gap analysis --}}
    @if(! empty($gaps))
    <div class="card bg-base-100 border border-base-200 shadow-sm">
        <div class="card-body">
            <h3 class="font-semibold text-sm mb-1">Phân tích khoảng cách & Ưu tiên hành động</h3>
            <p class="text-xs text-base-content/50 mb-4">Các lĩnh vực cần cải thiện, sắp xếp theo mức độ ưu tiên</p>

            <div class="space-y-4">
                @foreach($gaps as $gap)
                <div class="border border-base-200 rounded-box p-4">
                    <div class="flex items-center gap-2 mb-2">
                        <span class="badge badge-sm
                            {{ $gap['priority'] === 'high' ? 'badge-error' : ($gap['priority'] === 'medium' ? 'badge-warning' : 'badge-info') }}">
                            {{ $gap['priority'] === 'high' ? 'Cao' : ($gap['priority'] === 'medium' ? 'Trung bình' : 'Thấp') }}
                        </span>
                        <span class="font-semibold text-sm">{{ $gap['label'] }}</span>
                        <span class="ml-auto text-xs text-base-content/50">{{ $gap['score'] }}/100</span>
                    </div>
                    <p class="text-sm font-medium text-primary mb-2">{{ $gap['title'] }}</p>
                    <ul class="space-y-1">
                        @foreach($gap['actions'] as $action)
                        <li class="flex gap-2 text-xs text-base-content/70">
                            <span class="text-base-content/30 shrink-0">→</span>
                            {{ $action }}
                        </li>
                        @endforeach
                    </ul>
                </div>
                @endforeach
            </div>
        </div>
    </div>
    @else
    <div class="alert alert-success">
        <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/>
        </svg>
        <span>Tất cả các domain đều đạt điểm tốt. Tổ chức đã sẵn sàng triển khai!</span>
    </div>
    @endif

    @endif
</div>
@endsection
