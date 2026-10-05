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
        $producerMoreUrl = match (true) {
            (bool) $subject?->is_active => route('ocop-subject.public.show', ['slug' => $subject->slug]),
            (bool) $heritageSite => route('heritage.public.show', ['slug' => $heritageSite->slug, 'id' => $heritageSite->id]),
            (bool) $product->province_code => route('ocop.public.index', ['province' => $product->province_code]),
            default => null,
        };
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
                    @php
                        $brandName = $subject?->name ?? $product->producer_name;
                        $brandAddress = $subject?->fullAddress() ?: $address;
                    @endphp
                    @if($brandName)
                    @php
                        $brandDesc = collect([$subject?->organization_type?->label(), $brandAddress])->filter()->implode(' · ');
                        $brandContacts = collect([
                            'Hotline' => $subject?->hotline ? ['tel:'.preg_replace('/[^0-9+]/', '', $subject->hotline), $subject->hotline, false] : null,
                            'Email' => $subject?->email ? ['mailto:'.$subject->email, $subject->email, false] : null,
                            'Website' => $subject?->website ? [$subject->website, preg_replace('#^https?://#', '', rtrim($subject->website, '/')), true] : null,
                        ])->filter();
                    @endphp
                    <div class="overflow-hidden rounded-sm bg-[#f8f9fa]">
                        <div class="border-b border-[#e0e0e0] px-4 py-3 text-base font-semibold text-[#333]">Thương hiệu</div>
                        <div class="flex items-center gap-4 p-4">
                            @if($subjectImages->isNotEmpty())
                            <a href="{{ $subjectImages[0]['full'] }}" target="_blank" rel="noopener"
                               class="flex h-16 w-16 shrink-0 items-center justify-center overflow-hidden rounded-lg border border-[#e0e0e0] bg-white">
                                <img src="{{ $subjectImages[0]['thumb'] }}" alt="{{ $brandName }}" class="h-full w-full object-contain">
                            </a>
                            @else
                            <div class="flex h-16 w-16 shrink-0 items-center justify-center rounded-lg border border-[#e0e0e0] bg-white text-2xl font-bold text-[#117a3a]">
                                {{ mb_strtoupper(mb_substr($brandName, 0, 1)) }}
                            </div>
                            @endif
                            <div class="flex min-w-0 flex-col gap-1">
                                <h4 class="font-bold uppercase leading-snug text-[#117a3a]">{{ $brandName }}</h4>
                                @if($brandDesc)
                                <p class="line-clamp-2 text-sm leading-relaxed text-[#555]">{{ $brandDesc }}</p>
                                @endif
                                @if($producerMoreUrl)
                                <a href="{{ $producerMoreUrl }}" class="inline-flex items-center gap-0.5 self-start text-sm font-medium text-[#117a3a] hover:underline">
                                    Xem thêm
                                    <svg class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/></svg>
                                </a>
                                @endif
                            </div>
                        </div>
                        @if($brandContacts->isNotEmpty())
                        <dl class="space-y-1.5 border-t border-[#e0e0e0] px-4 py-3 text-sm">
                            @foreach($brandContacts as $label => [$href, $text, $external])
                            <div class="flex gap-2">
                                <dt class="shrink-0 text-[#555]">{{ $label }}:</dt>
                                <dd class="min-w-0 break-all">
                                    <a href="{{ $href }}" @if($external) target="_blank" rel="noopener nofollow" @endif class="text-[#333] hover:text-[#117a3a] hover:underline">{{ $text }}</a>
                                </dd>
                            </div>
                            @endforeach
                        </dl>
                        @endif
                    </div>
                    @endif

                    <div>
                        <div class="overflow-hidden rounded-sm bg-[#f8f9fa]">
                            <table class="w-full table-fixed border-collapse text-sm text-[#333]">
                                <tbody>
                                    @foreach($specs as $label => $value)
                                    <tr class="border-b border-[#e5e5e5] align-top last:border-b-0">
                                        <th scope="row" class="w-[38%] px-4 py-3 text-left font-semibold">{{ $label }}:</th>
                                        <td class="px-4 py-3 font-normal break-words">
                                            @if(filled($value))
                                            {!! nl2br(e($value)) !!}
                                            @else
                                            <span class="text-[#999]">Đang cập nhật</span>
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
