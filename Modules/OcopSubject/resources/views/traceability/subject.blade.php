@extends('layouts.traceability')

@section('content')
    @include('traceability.partials.hero')

    @if ($cert)
        <div @class(['mx-4 mt-4 flex items-center gap-3 rounded-xl px-3 py-3', 'bg-green-50 text-green-900 border border-green-200' => $cert['valid'], 'bg-red-50 text-red-900 border border-red-200' => ! $cert['valid']])>
            <i class="fa-solid {{ $cert['valid'] ? 'fa-award' : 'fa-circle-exclamation' }} text-2xl" aria-hidden="true"></i>
            <div>
                <p class="text-sm font-bold">{{ $cert['valid'] ? 'Chứng nhận OCOP còn hiệu lực' : 'Chứng nhận OCOP đã hết hạn' }}</p>
                <p class="text-xs opacity-80">{{ $cert['valid'] ? 'Hiệu lực đến' : 'Hết hạn ngày' }} {{ $cert['label'] }}@if ($stars) · Hạng {{ $stars }} sao @endif</p>
            </div>
        </div>
    @endif

    @include('traceability.partials.fact-grid', ['facts' => $facts])

    @if (array_filter($contact))
        <section class="px-4 mt-5">
            <h2 class="text-sm font-bold mb-2 flex items-center gap-1.5"><i class="fa-solid fa-phone text-primary" aria-hidden="true"></i> Liên hệ</h2>
            <ul class="rounded-xl border border-base-200 divide-y divide-base-200 text-sm">
                @if (! empty($contact['address']))
                    <li class="flex items-start gap-2.5 px-3 py-2.5">
                        <i class="fa-solid fa-location-dot text-base-content/50 mt-0.5" aria-hidden="true"></i>
                        <div class="flex-1">
                            <p>{{ $contact['address'] }}</p>
                            @if ($contact['map_url'])<a href="{{ $contact['map_url'] }}" target="_blank" rel="noopener" class="link link-primary text-xs">Chỉ đường</a>@endif
                        </div>
                    </li>
                @endif
                @if (! empty($contact['hotline']))
                    <li class="flex items-center gap-2.5 px-3 py-2.5"><i class="fa-solid fa-phone text-base-content/50" aria-hidden="true"></i><a href="tel:{{ preg_replace('/[^0-9+]/', '', $contact['hotline']) }}" class="link link-hover font-medium">{{ $contact['hotline'] }}</a></li>
                @endif
                @if (! empty($contact['email']))
                    <li class="flex items-center gap-2.5 px-3 py-2.5"><i class="fa-solid fa-envelope text-base-content/50" aria-hidden="true"></i><a href="mailto:{{ $contact['email'] }}" class="link link-hover break-all">{{ $contact['email'] }}</a></li>
                @endif
                @if (! empty($contact['website']))
                    <li class="flex items-center gap-2.5 px-3 py-2.5"><i class="fa-solid fa-globe text-base-content/50" aria-hidden="true"></i><a href="{{ $contact['website'] }}" target="_blank" rel="noopener nofollow" class="link link-hover break-all">{{ $contact['website'] }}</a></li>
                @endif
            </ul>
        </section>
    @endif

    @if ($docs)
        @include('traceability.partials.doc-status', ['docs' => $docs, 'heading' => 'Giấy phép & chứng nhận'])
    @endif

    @include('traceability.partials.sections')
    @include('traceability.partials.related')
    @include('traceability.partials.timeline', ['heading' => 'Hành trình hồ sơ'])
@endsection
