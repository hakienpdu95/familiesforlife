@if($products->isNotEmpty())
<section class="py-10 border-t border-base-200">
    <div class="container">
        <div class="flex items-center justify-between mb-6">
            <h2 class="text-xl font-bold text-base-content">Sản phẩm OCOP {{ $province->name }}</h2>
            <a href="{{ route('ocop.public.index', ['province' => $province->province_code]) }}" class="text-sm font-semibold text-primary hover:underline">Xem tất cả OCOP {{ $province->name }} →</a>
        </div>
        <div class="grid grid-cols-2 sm:grid-cols-3 lg:grid-cols-6 gap-5">
            @foreach($products as $product)
            <a href="{{ route('ocop.public.show', ['slug' => $product->slug]) }}" class="group flex flex-col gap-2">
                <div class="aspect-square rounded-xl overflow-hidden bg-base-200">
                    @if($product->image_path)
                    <img src="{{ Illuminate\Support\Facades\Storage::url($product->image_path) }}" alt="{{ $product->name }}" class="h-full w-full object-cover" loading="lazy">
                    @else
                    <div class="ph h-full w-full"></div>
                    @endif
                </div>
                <div>
                    <h3 class="text-sm font-bold leading-snug group-hover:text-primary line-clamp-2">{{ $product->name }}</h3>
                    <p class="mt-0.5 text-xs text-warning">{{ str_repeat('★', $product->star_rating) }}</p>
                </div>
            </a>
            @endforeach
        </div>
    </div>
</section>
@endif
