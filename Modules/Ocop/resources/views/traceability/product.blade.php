@extends('layouts.traceability')

@section('content')
    @include('traceability.partials.hero', ['imageClass' => 'w-full aspect-square object-contain', 'imageWidth' => 600, 'imageHeight' => 600])

    @if ($subject)
        @if ($stars)
            <div class="mx-4 mt-4 flex items-center gap-3 rounded-xl border border-amber-200 bg-amber-50 px-3 py-3">
                <span class="flex items-center justify-center w-12 h-12 rounded-full bg-amber-400 text-white text-xl font-bold shrink-0">{{ $stars }}</span>
                <div>
                    <p class="text-amber-500 leading-none" aria-label="{{ $stars }} sao">
                        @for ($i = 1; $i <= 5; $i++)<i class="fa-solid fa-star {{ $i <= $stars ? '' : 'opacity-25' }}" aria-hidden="true"></i>@endfor
                    </p>
                    <p class="text-sm font-bold text-amber-900 mt-1">Sản phẩm OCOP {{ $stars }} sao</p>
                    <p class="text-[11px] text-amber-800/70">Chương trình Mỗi xã một sản phẩm</p>
                </div>
            </div>
        @endif

        @if ($channels)
            <section id="mua-hang" class="mx-4 mt-4 scroll-mt-4 rounded-xl border border-base-200 overflow-hidden" aria-label="Kênh mua chính hãng">
                <div class="flex items-center justify-between gap-2 px-3 py-2.5 bg-base-200/60">
                    <h2 class="text-sm font-bold flex items-center gap-1.5"><i class="fa-solid fa-store text-primary" aria-hidden="true"></i> Mua sản phẩm chính hãng</h2>
                    <span class="badge badge-success badge-sm gap-1 text-white"><i class="fa-solid fa-circle-check" aria-hidden="true"></i>Đã xác minh</span>
                </div>
                <ul class="divide-y divide-base-200">
                    @foreach ($channels as $channel)
                        <li>
                            <a href="{{ $channel['url'] }}" target="_blank" rel="noopener nofollow sponsored"
                               class="flex items-center gap-3 px-3 py-3 hover:bg-base-200/50 active:bg-base-200 transition-colors">
                                <span class="flex items-center justify-center w-10 h-10 rounded-xl shrink-0 {{ $channel['tone'] }}">
                                    <i class="{{ $channel['icon'] }} text-lg" aria-hidden="true"></i>
                                </span>
                                <span class="min-w-0 flex-1">
                                    <span class="block text-sm font-semibold">{{ $channel['name'] }}</span>
                                    <span class="block text-xs text-base-content/50 truncate">Gian hàng chính thức · {{ $channel['host'] }}</span>
                                </span>
                                <i class="fa-solid fa-up-right-from-square text-xs text-base-content/40" aria-hidden="true"></i>
                            </a>
                        </li>
                    @endforeach
                </ul>
                <p class="flex items-start gap-1.5 px-3 py-2 text-[11px] text-base-content/50 bg-base-200/40">
                    <i class="fa-solid fa-circle-info mt-0.5" aria-hidden="true"></i>
                    Chỉ mua qua các kênh trên để đảm bảo đúng sản phẩm OCOP. Giá và tồn kho do sàn cung cấp.
                </p>
            </section>
        @endif

        @include('traceability.partials.fact-grid', ['facts' => $facts, 'heading' => 'Thông tin sản phẩm'])

        <section class="px-4 mt-5">
            <h2 class="text-sm font-bold mb-2 flex items-center gap-1.5"><i class="fa-solid fa-building text-primary" aria-hidden="true"></i> Chủ thể sản xuất</h2>
            <div class="rounded-xl border border-base-200 p-3 space-y-2">
                <div class="flex items-start justify-between gap-2">
                    <div class="min-w-0">
                        <p class="font-semibold">{{ $subject['name'] ?? 'Đang cập nhật' }}</p>
                        @if (! empty($subject['type']))<p class="text-xs text-base-content/60">{{ $subject['type'] }}</p>@endif
                    </div>
                    @if (! empty($subject['stars']))
                        <span class="badge badge-warning badge-sm gap-1 shrink-0"><i class="fa-solid fa-star" aria-hidden="true"></i>{{ $subject['stars'] }} sao</span>
                    @endif
                </div>
                @if ($subject['cert_expiry_label'])
                    <p @class(['flex items-center gap-1.5 text-xs font-medium rounded-lg px-2 py-1.5', 'bg-green-50 text-green-800' => $subject['cert_valid'], 'bg-red-50 text-red-800' => ! $subject['cert_valid']])>
                        <i class="fa-solid {{ $subject['cert_valid'] ? 'fa-circle-check' : 'fa-circle-exclamation' }}" aria-hidden="true"></i>
                        Chứng nhận OCOP {{ $subject['cert_valid'] ? 'còn hiệu lực đến' : 'đã hết hạn ngày' }} {{ $subject['cert_expiry_label'] }}
                    </p>
                @endif
                @if (! empty($subject['address']))
                    <p class="flex items-start gap-1.5 text-xs text-base-content/70"><i class="fa-solid fa-location-dot mt-0.5" aria-hidden="true"></i>{{ $subject['address'] }}</p>
                @endif
                <div class="flex flex-wrap gap-2 pt-1">
                    @if (! empty($subject['hotline']))
                        <a href="tel:{{ preg_replace('/[^0-9+]/', '', $subject['hotline']) }}" class="btn btn-success btn-sm gap-1.5"><i class="fa-solid fa-phone" aria-hidden="true"></i>{{ $subject['hotline'] }}</a>
                    @endif
                    @if (! empty($subject['website']))
                        <a href="{{ $subject['website'] }}" target="_blank" rel="noopener nofollow" class="btn btn-outline btn-sm gap-1.5"><i class="fa-solid fa-globe" aria-hidden="true"></i>Website</a>
                    @endif
                    @if ($subject['url'])
                        <a href="{{ $subject['url'] }}" class="btn btn-ghost btn-sm gap-1.5"><i class="fa-solid fa-store" aria-hidden="true"></i>Trang chủ thể</a>
                    @endif
                </div>
            </div>
        </section>

        @if ($quality_docs)
            @include('traceability.partials.doc-status', ['docs' => $quality_docs, 'heading' => 'Hồ sơ chất lượng'])
        @endif

        @include('traceability.partials.sections')
        @include('traceability.partials.related')
    @endif
@endsection
