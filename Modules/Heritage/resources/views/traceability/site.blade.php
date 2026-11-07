@extends('layouts.traceability')

@section('content')
    @include('traceability.partials.hero')

    @if ($visiting)
        <div @class([
            'mx-4 mt-4 flex items-center gap-2.5 rounded-xl px-3 py-2.5 text-sm font-semibold',
            'bg-green-50 text-green-800' => $visiting['tone'] === 'success',
            'bg-amber-50 text-amber-800' => $visiting['tone'] === 'warning',
            'bg-red-50 text-red-800' => $visiting['tone'] === 'error',
        ])>
            <span class="relative flex w-2.5 h-2.5">
                <span @class(['absolute inline-flex h-full w-full rounded-full opacity-60 animate-ping', 'bg-green-400' => $visiting['tone'] === 'success', 'bg-amber-400' => $visiting['tone'] === 'warning', 'bg-red-400' => $visiting['tone'] === 'error'])></span>
                <span @class(['relative inline-flex w-2.5 h-2.5 rounded-full', 'bg-green-500' => $visiting['tone'] === 'success', 'bg-amber-500' => $visiting['tone'] === 'warning', 'bg-red-500' => $visiting['tone'] === 'error'])></span>
            </span>
            Tình trạng tham quan: {{ $visiting['label'] }}
        </div>
    @endif

    @include('traceability.partials.fact-grid', ['facts' => $facts])

    @if ($address || $map_url)
        <section class="px-4 mt-5">
            <h2 class="text-sm font-bold mb-2 flex items-center gap-1.5"><i class="fa-solid fa-map-location-dot text-primary" aria-hidden="true"></i> Vị trí</h2>
            <div class="rounded-xl border border-base-200 p-3">
                <p class="text-sm font-medium">{{ $address ?: 'Địa chỉ đang cập nhật' }}</p>
                @if ($coordinates)<p class="text-xs text-base-content/50 mt-0.5 font-mono">GPS: {{ $coordinates }}</p>@endif
                @if ($map_url)
                    <a href="{{ $map_url }}" target="_blank" rel="noopener" class="btn btn-outline btn-primary btn-sm mt-2 gap-1.5"><i class="fa-solid fa-route" aria-hidden="true"></i>Chỉ đường trên Google Maps</a>
                @endif
            </div>
        </section>
    @endif

    @include('traceability.partials.sections')
    @include('traceability.partials.timeline', ['heading' => 'Hành trình số hoá'])
    @include('traceability.partials.related')
@endsection
