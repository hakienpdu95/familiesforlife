@extends('layouts.traceability')

@section('content')
    @if ($images)
        <div class="flex snap-x snap-mandatory overflow-x-auto bg-base-200 [scrollbar-width:none] [&::-webkit-scrollbar]:hidden">
            @foreach ($images as $i => $image)
                <img src="{{ $image }}" alt="{{ $title }}" width="960" height="600" class="snap-center shrink-0 w-full aspect-[16/10] object-cover" @if ($i === 0) fetchpriority="high" @else loading="lazy" @endif decoding="async">
            @endforeach
        </div>
    @endif

    <div class="px-5 pt-6">
        <p class="tx-label !text-primary">Chủ thể OCOP{{ $type ? ' · '.$type : '' }}</p>
        <h1 class="tx-serif text-[26px] font-bold leading-tight mt-1.5">{{ $title }}</h1>
        @if ($subtitle)<p class="mt-1 text-[15px] text-base-content/60 italic">{{ $subtitle }}</p>@endif

        @if ($stars || $cert)
            <div class="mt-5 flex items-center gap-4 py-4 border-y border-base-300">
                @if ($stars)
                    <div class="w-[68px] h-[68px] rounded-full border-2 border-[#c99a2e] flex flex-col items-center justify-center shrink-0 text-[#8a6413]">
                        <span class="text-[10px] font-bold tracking-[0.15em]">OCOP</span>
                        <span class="text-xl font-bold leading-none tx-serif">{{ $stars }}★</span>
                    </div>
                @endif
                <div class="min-w-0">
                    <p class="font-semibold">{{ $stars ? 'Chủ thể đạt hạng OCOP '.$stars.' sao' : 'Chủ thể OCOP' }}</p>
                    @if ($cert)
                        <p @class(['text-sm mt-0.5', 'text-base-content/60' => $cert['valid'], 'text-error' => ! $cert['valid']])>
                            Chứng nhận {{ $cert['valid'] ? 'hiệu lực đến' : 'đã hết hạn ngày' }} {{ $cert['label'] }}
                        </p>
                    @endif
                </div>
            </div>
        @endif
    </div>

    <section class="px-5 mt-7">
        <h2 class="tx-label mb-1">Thông tin chủ thể</h2>
        @include('traceability.partials.spec', ['rows' => [
            $type ? ['label' => 'Loại hình', 'value' => $type] : null,
            $products_count !== null ? ['label' => 'Sản phẩm OCOP', 'value' => $products_count.' sản phẩm'] : null,
            $is_food_business ? ['label' => 'Ngành nghề', 'value' => 'Cơ sở sản xuất thực phẩm'] : null,
            ! empty($contact['address']) ? ['label' => 'Địa chỉ', 'value' => $contact['address']] : null,
        ]])
        <div class="flex flex-wrap gap-2 mt-4">
            @if (! empty($contact['hotline']))<a href="tel:{{ preg_replace('/[^0-9+]/', '', $contact['hotline']) }}" class="tx-chip">@include('traceability.partials.icon', ['name' => 'phone']) {{ $contact['hotline'] }}</a>@endif
            @if (! empty($contact['email']))<a href="mailto:{{ $contact['email'] }}" class="tx-chip">@include('traceability.partials.icon', ['name' => 'mail']) Email</a>@endif
            @if (! empty($contact['website']))<a href="{{ $contact['website'] }}" target="_blank" rel="noopener nofollow" class="tx-chip">@include('traceability.partials.icon', ['name' => 'globe']) Website</a>@endif
            @if (! empty($contact['map_url']))<a href="{{ $contact['map_url'] }}" target="_blank" rel="noopener" class="tx-chip">@include('traceability.partials.icon', ['name' => 'map']) Chỉ đường</a>@endif
        </div>
    </section>

    @if ($docs)
        <section class="px-5 mt-8">
            <h2 class="tx-label mb-1">Giấy phép và chứng nhận</h2>
            @include('traceability.partials.checklist', ['items' => $docs])
        </section>
    @endif

    @if ($sections)
        <section class="px-5 mt-8">
            @include('traceability.partials.accordion', ['sections' => $sections, 'openFirst' => true])
        </section>
    @endif

    @include('traceability.partials.related')

@endsection
