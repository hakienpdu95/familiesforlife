@php
    $productUrl = route('ocop.public.show', ['slug' => $product->slug, 'id' => $product->id]);
@endphp
<div class="group flex flex-col overflow-hidden rounded-sm border border-[#e0e0e0] bg-white transition hover:shadow-md">
    <a href="{{ $productUrl }}" class="block aspect-square overflow-hidden bg-base-200">
        <img src="{{ $product->imageUrl('medium') ?: asset('images/post-cover-placeholder.svg') }}" alt="{{ $product->name }}"
             class="h-full w-full object-cover transition duration-300 group-hover:scale-105" loading="lazy">
    </a>
    <div class="flex flex-1 flex-col gap-1.5 p-3">
        @if($product->category)
        <span class="line-clamp-1 text-[11px] font-semibold uppercase tracking-wide text-[#117a3a]">{{ $product->category->name }}</span>
        @endif
        <a href="{{ $productUrl }}" class="line-clamp-2 text-sm font-semibold leading-snug text-[#333] hover:text-[#117a3a]">{{ $product->name }}</a>
        <div class="mt-auto flex items-center justify-between gap-2 pt-1">
            <div class="flex items-center gap-1" title="Hạng {{ $product->star_rating }} sao OCOP">
                <div class="rating rating-xs pointer-events-none">
                    @for($s = 1; $s <= 5; $s++)
                    <div class="mask mask-star-2 {{ $s <= $product->star_rating ? 'bg-warning' : 'bg-base-300' }}"></div>
                    @endfor
                </div>
            </div>
            @if($product->purchase_url)
            <a href="{{ $product->purchase_url }}" target="_blank" rel="noopener nofollow" title="Mua sản phẩm"
               class="btn btn-circle btn-xs border-0 bg-[#117a3a] text-white hover:bg-[#0d6230]">
            @else
            <a href="{{ $productUrl }}" title="Xem sản phẩm"
               class="btn btn-circle btn-xs border-0 bg-[#e8f3ec] text-[#117a3a] hover:bg-[#117a3a] hover:text-white">
            @endif
                <svg class="h-3.5 w-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 3h2l.4 2M7 13h10l4-8H5.4M7 13L5.4 5M7 13l-2.293 2.293c-.63.63-.184 1.707.707 1.707H17m0 0a2 2 0 100 4 2 2 0 000-4zm-8 2a2 2 0 11-4 0 2 2 0 014 0z"/></svg>
            </a>
        </div>
    </div>
</div>
