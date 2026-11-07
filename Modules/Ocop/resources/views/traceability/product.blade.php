@extends('layouts.traceability')

@section('content')
    @if ($image)
        <div class="bg-base-200 px-4 py-5">
            <img src="{{ $image }}" alt="{{ $title }}" width="400" height="400" class="w-56 h-56 mx-auto object-contain rounded-lg bg-base-100">
        </div>
    @endif

    <div class="px-4 pt-5 space-y-5">
        <div>
            @if ($category)
                <p class="text-xs font-medium text-base-content/50 mb-1">{{ $category }}</p>
            @endif
            <h1 class="text-xl font-bold leading-snug">{{ $title }}</h1>
        </div>

        @if ($facts)
        <div class="flex items-center gap-3 rounded-lg border border-amber-200 bg-amber-50 px-3 py-3">
            <span class="flex items-center justify-center w-11 h-11 rounded-full bg-amber-400 text-white text-lg font-bold shrink-0">{{ $stars ?: '—' }}</span>
            <div>
                <p class="text-amber-500 text-base leading-none" aria-label="{{ $stars ? $stars.' sao' : 'Chưa xếp hạng' }}">
                    @for ($i = 1; $i <= 5; $i++)
                        <i class="fa-solid fa-star {{ $stars && $i <= $stars ? '' : 'opacity-25' }}" aria-hidden="true"></i>
                    @endfor
                </p>
                <p class="text-xs font-semibold text-amber-900 mt-1">{{ $stars ? 'Sản phẩm OCOP '.$stars.' sao' : 'Chưa xếp hạng OCOP' }}</p>
            </div>
        </div>

        <section>
            <h2 class="text-sm font-bold mb-2">Chủ thể sản xuất</h2>
            <div class="flex items-start gap-3 rounded-lg bg-base-200 px-3 py-3">
                <i class="fa-solid fa-industry text-primary mt-0.5" aria-hidden="true"></i>
                <div class="text-sm">
                    <p class="font-semibold">{{ $subject ?: 'Đang cập nhật' }}</p>
                    @if ($producer)
                        <p class="text-base-content/70 mt-0.5">Nhà sản xuất: {{ $producer }}</p>
                    @endif
                    @if ($producer_address)
                        <p class="text-base-content/60 mt-0.5">{{ $producer_address }}</p>
                    @endif
                </div>
            </div>
        </section>

        @if ($quality_docs)
            <section>
                <h2 class="text-sm font-bold mb-2">Tiêu chuẩn &amp; hồ sơ chất lượng</h2>
                <ul class="space-y-1.5 text-sm">
                    @foreach ($quality_docs as $doc)
                        <li class="flex items-center gap-2"><i class="fa-solid fa-certificate text-success" aria-hidden="true"></i>{{ $doc }}</li>
                    @endforeach
                </ul>
            </section>
        @endif

        <section>
            <h2 class="text-sm font-bold mb-2">Thông tin sản phẩm</h2>
            @include('traceability.partials.facts', ['facts' => $facts])
        </section>
        @endif

        @if ($description || filled($content_html))
            <section>
                <h2 class="text-sm font-bold mb-2">Câu chuyện sản phẩm</h2>
                @if ($description)
                    <p class="text-sm leading-relaxed text-base-content/80 mb-3">{{ $description }}</p>
                @endif
                @if (filled($content_html))
                    <div class="prose prose-sm max-w-none prose-img:rounded-lg prose-img:mx-auto prose-a:text-primary [&_table]:block [&_table]:overflow-x-auto [&_iframe]:w-full [&_iframe]:aspect-video">
                        {!! $content_html !!}
                    </div>
                @endif
            </section>
        @endif

                @include('traceability.partials.sections', ['sections' => $sections])
    </div>
@endsection
