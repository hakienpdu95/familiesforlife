@extends('layouts.frontend')

@section('title', $product->name)
@section('meta_description', $product->description ?: $product->name)

@section('content')
@php
    $placeholder = asset('images/post-cover-placeholder.svg');
    $address = trim(collect([$product->producer_address, $product->ward_name, $product->province_name])->filter()->implode(', '), ', ');
@endphp
<div class="container">

    <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">

        <div x-data="ocopGallery({{ $images->count() }})" @keydown.escape.window="close()">
            @if($images->isEmpty())
            <div class="aspect-square overflow-hidden bg-base-200">
                <img src="{{ $placeholder }}" alt="{{ $product->name }}" class="h-full w-full object-cover">
            </div>
            @else
            <div class="relative">
                <div class="swiper overflow-hidden bg-base-200" x-ref="main">
                    <div class="swiper-wrapper">
                        @foreach($images as $i => $image)
                        <div class="swiper-slide">
                            <button type="button" class="block w-full aspect-square cursor-zoom-in" @click="open({{ $i }})">
                                <img src="{{ $image['full'] }}" alt="{{ $product->name }} - ảnh {{ $i + 1 }}"
                                     class="h-full w-full object-cover" @if($i > 0) loading="lazy" @endif>
                            </button>
                        </div>
                        @endforeach
                    </div>
                </div>
                <span class="badge badge-neutral badge-sm absolute bottom-3 right-3 z-10 opacity-80"
                      x-text="(active + 1) + ' / ' + total">1 / {{ $images->count() }}</span>
            </div>

            @if($images->count() > 1)
            <div class="swiper mt-3" x-ref="thumbs">
                <div class="swiper-wrapper">
                    @foreach($images as $i => $image)
                    <div class="swiper-slide">
                        @if($loop->last)
                        <button type="button" class="relative block w-full aspect-square overflow-hidden border-1 border-base-300"
                                @click="open({{ $i }})">
                            <img src="{{ $image['thumb'] }}" alt="" class="h-full w-full object-cover" loading="lazy">
                            <span class="absolute inset-0 flex items-center justify-center bg-black/55 text-xs font-semibold text-white">Xem thêm</span>
                        </button>
                        @else
                        <button type="button" class="block w-full aspect-square overflow-hidden border-1 transition"
                                :class="active === {{ $i }} ? 'border-primary' : 'border-base-300 opacity-70 hover:opacity-100'"
                                @click="go({{ $i }})">
                            <img src="{{ $image['thumb'] }}" alt="" class="h-full w-full object-cover" loading="lazy">
                        </button>
                        @endif
                    </div>
                    @endforeach
                </div>
            </div>
            @endif

            <div class="modal" :class="{ 'modal-open': lightbox }" role="dialog" aria-label="Thư viện ảnh {{ $product->name }}">
                <div class="modal-box w-11/12 max-w-5xl p-0 bg-base-100 relative">
                    <button type="button" class="btn btn-sm btn-circle btn-ghost absolute right-2 top-2 z-20" @click="close()" aria-label="Đóng">✕</button>
                    <div class="swiper" x-ref="lightbox">
                        <div class="swiper-wrapper">
                            @foreach($images as $i => $image)
                            <div class="swiper-slide flex items-center justify-center bg-base-200">
                                <img src="{{ $image['full'] }}" alt="{{ $product->name }} - ảnh {{ $i + 1 }}"
                                     class="max-h-[80vh] w-full object-contain" loading="lazy">
                            </div>
                            @endforeach
                        </div>
                        <button type="button" class="swiper-button-prev" x-ref="prev" aria-label="Ảnh trước"></button>
                        <button type="button" class="swiper-button-next" x-ref="next" aria-label="Ảnh sau"></button>
                    </div>
                    <p class="py-2 text-center text-sm text-base-content/60" x-text="(lightboxIndex + 1) + ' / ' + total"></p>
                </div>
                <div class="modal-backdrop bg-black/70" @click="close()"></div>
            </div>
            @endif
        </div>

        <div class="space-y-4">
            <h1 class="text-2xl lg:text-3xl font-medium text-base-content leading-tight">{{ $product->name }}</h1>

            <div class="mt-3 flex items-center gap-2" title="Hạng {{ $product->star_rating }} sao OCOP">
                <div class="rating rating-sm pointer-events-none">
                    @for($s = 1; $s <= 5; $s++)
                    <div class="mask mask-star-2 {{ $s <= $product->star_rating ? 'bg-warning' : 'bg-base-300' }}"></div>
                    @endfor
                </div>
                <span class="text-sm text-base-content/60">{{ $product->star_rating }} sao OCOP</span>
            </div>

            <div class="mt-4 flex flex-wrap gap-2">
                <span class="badge badge-success gap-1 text-white">
                    <svg class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M5 13l4 4L19 7"/></svg>
                    Sản phẩm OCOP
                </span>
                @if($product->province_name)
                <span class="badge badge-outline">Xuất xứ: {{ $product->province_name }}</span>
                @endif
                @if($product->producer_name)
                <span class="badge badge-ghost">Thương hiệu: {{ $product->producer_name }}</span>
                @endif
            </div>

            @if($product->description)
            <p class="mt-4 text-base-content/80 leading-relaxed">{{ $product->description }}</p>
            @endif

            <h2 class="text-xs font-semibold text-base-content/50 uppercase tracking-wide mb-3">Thông tin sản phẩm</h2>

            <div class="border border-base-300 rounded-lg p-4 flex gap-3 mb-3">
                <svg class="w-6 h-6 shrink-0 text-primary" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z"/></svg>
                <div>
                    <p class="text-sm font-semibold">Chứng nhận OCOP {{ $product->star_rating }} sao</p>
                    <p class="text-sm text-base-content/60">Sản phẩm được đánh giá, phân hạng theo Chương trình Mỗi xã một sản phẩm.</p>
                </div>
            </div>

            <div class="border border-base-300 rounded-lg p-4 flex gap-3 mb-3">
                <svg class="w-6 h-6 shrink-0 text-primary" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4"/></svg>
                <div>
                    <p class="text-sm font-semibold">Nhà sản xuất</p>
                    <p class="text-sm text-base-content/60">{{ $product->producer_name ?: 'Chưa cập nhật thông tin.' }}</p>
                </div>
            </div>

            <div class="border border-base-300 rounded-lg p-4 flex gap-3 mb-3">
                <svg class="w-6 h-6 shrink-0 text-primary" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z"/><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M15 11a3 3 0 11-6 0 3 3 0 016 0z"/></svg>
                <div>
                    <p class="text-sm font-semibold">Địa chỉ</p>
                    <p class="text-sm text-base-content/60">{{ $address ?: 'Chưa cập nhật thông tin.' }}</p>
                </div>
            </div>

            @if($heritageSite)
            <div class="border border-base-300 rounded-lg p-4 flex gap-3 mb-3">
                <svg class="w-6 h-6 shrink-0 text-primary" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M3 21h18M5 21V10l7-5 7 5v11M9 21v-6h6v6"/></svg>
                <div>
                    <p class="text-sm font-semibold">Làng nghề / di tích</p>
                    <a href="{{ route('heritage.public.show', ['slug' => $heritageSite->slug, 'id' => $heritageSite->id]) }}" class="link link-primary text-sm">
                        {{ $heritageSite->name }}
                    </a>
                </div>
            </div>
            @endif

            @if($product->purchase_url)
            <a href="{{ $product->purchase_url }}" target="_blank" rel="noopener nofollow"
               class="btn btn-primary btn-lg w-full rounded-xl gap-2 mt-2">
                <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 3h2l.4 2M7 13h10l4-8H5.4M7 13L5.4 5M7 13l-2.293 2.293c-.63.63-.184 1.707.707 1.707H17m0 0a2 2 0 100 4 2 2 0 000-4zm-8 2a2 2 0 11-4 0 2 2 0 014 0z"/></svg>
                Mua sản phẩm
            </a>
            @endif
        </div>
    </div>

    @php
        $specs = [
            'Mã nhóm' => $product->category?->fullCode(),
            'Mã sản phẩm' => 'OP'.$product->id,
            'Xuất xứ' => $product->origin ?: $product->province_name,
            'Ngày sản xuất' => $product->production_date,
            'Hạn sử dụng' => $product->shelf_life,
            'Thành phần' => $product->ingredients,
            'Hướng dẫn sử dụng' => $product->usage_instructions,
            'Hướng dẫn bảo quản' => $product->storage_instructions,
        ];
        $producerMoreUrl = $heritageSite
            ? route('heritage.public.show', ['slug' => $heritageSite->slug, 'id' => $heritageSite->id])
            : ($product->province_code ? route('ocop.public.index', ['province' => $product->province_code]) : null);
    @endphp

    <section class="mt-12">
        <div class="flex">
            <h2 class="bg-primary px-5 py-2.5 text-sm font-bold uppercase tracking-wide text-primary-content">
                Thông tin sản phẩm
            </h2>
        </div>

        <div class="border border-base-300 bg-base-100 p-3">
            <div class="grid grid-cols-1 lg:grid-cols-12 gap-8">

                <div class="lg:col-span-8 min-w-0">
                    @if(filled($product->story))
                    <div class="prose prose-sm sm:prose-base max-w-none prose-img:rounded-lg prose-img:mx-auto prose-a:text-primary [&_table]:block [&_table]:overflow-x-auto">
                        {!! $product->story !!}
                    </div>
                    @elseif($product->description)
                    <div class="prose prose-sm sm:prose-base max-w-none">
                        <p>{{ $product->description }}</p>
                    </div>
                    @else
                    <p class="text-sm text-base-content/50">Câu chuyện sản phẩm đang được cập nhật.</p>
                    @endif
                </div>

                <aside class="lg:col-span-4 space-y-6">
                    @if($product->producer_name)
                    <div class="rounded-xl border border-base-300 p-4">
                        <p class="text-xs font-semibold uppercase tracking-wide text-base-content/50 mb-3">Thương hiệu</p>
                        <div class="flex gap-3">
                            <div class="avatar avatar-placeholder shrink-0">
                                <div class="w-14 rounded-lg bg-primary/10 text-primary">
                                    <span class="text-xl font-bold">{{ mb_strtoupper(mb_substr($product->producer_name, 0, 1)) }}</span>
                                </div>
                            </div>
                            <div class="min-w-0">
                                <p class="font-semibold leading-snug">{{ $product->producer_name }}</p>
                                @if($address)
                                <p class="mt-1 text-sm text-base-content/60 line-clamp-2">{{ $address }}</p>
                                @endif
                                @if($producerMoreUrl)
                                <a href="{{ $producerMoreUrl }}" class="mt-1 inline-flex items-center gap-0.5 text-sm font-medium text-primary hover:underline">
                                    Xem thêm
                                    <svg class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/></svg>
                                </a>
                                @endif
                            </div>
                        </div>
                    </div>
                    @endif

                    <div>
                        <h3 class="text-base font-bold mb-3">Thông số kỹ thuật</h3>
                        <div class="overflow-hidden rounded-xl border border-base-300">
                            <table class="w-full text-sm">
                                <tbody class="divide-y divide-base-300">
                                    @foreach($specs as $label => $value)
                                    <tr class="even:bg-base-200/60 align-top">
                                        <th scope="row" class="w-2/5 px-3 py-2.5 text-left font-medium text-base-content/60">{{ $label }}</th>
                                        <td class="px-3 py-2.5 break-words">
                                            @if(filled($value))
                                            {!! nl2br(e($value)) !!}
                                            @else
                                            <span class="text-base-content/40">Đang cập nhật</span>
                                            @endif
                                        </td>
                                    </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>
                    </div>
                </aside>

            </div>
        </div>
    </section>

</div>
@endsection

@if($images->isNotEmpty())
@push('scripts')
    @vite(['resources/js/modules/swiper.js'], 'build/frontend')
    <script type="module">
        document.addEventListener('alpine:init', () => {
            window.Alpine.data('ocopGallery', (total) => ({
                total,
                active: 0,
                lightbox: false,
                lightboxIndex: 0,
                main: null,
                thumbs: null,
                big: null,

                init() {
                    if (this.$refs.thumbs) {
                        this.thumbs = window.initSwiper(this.$refs.thumbs, {
                            slidesPerView: 4.5,
                            spaceBetween: 8,
                            navigation: false,
                            pagination: false,
                            watchSlidesProgress: true,
                            breakpoints: { 640: { slidesPerView: 5.5 } },
                        });
                    }

                    this.main = window.initSwiper(this.$refs.main, {
                        navigation: false,
                        pagination: false,
                        spaceBetween: 0,
                        on: {
                            slideChange: (s) => {
                                this.active = s.activeIndex;
                                this.thumbs?.slideTo(Math.max(0, s.activeIndex - 1));
                            },
                        },
                    });
                },

                go(i) {
                    this.main?.slideTo(i);
                },

                open(i) {
                    this.lightboxIndex = i;
                    this.lightbox = true;
                    this.$nextTick(() => {
                        if (!this.big) {
                            this.big = window.initSwiper(this.$refs.lightbox, {
                                initialSlide: i,
                                pagination: false,
                                navigation: { nextEl: this.$refs.next, prevEl: this.$refs.prev },
                                on: { slideChange: (s) => { this.lightboxIndex = s.activeIndex; } },
                            });
                        } else {
                            this.big.update();
                            this.big.slideTo(i, 0);
                        }
                    });
                },

                close() {
                    if (!this.lightbox) return;
                    this.lightbox = false;
                    this.go(this.lightboxIndex);
                },
            }));
        });
    </script>
@endpush
@endif
