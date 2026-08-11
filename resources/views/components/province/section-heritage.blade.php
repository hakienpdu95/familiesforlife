{{-- spec/Heritage_Technical_Specification.md §7.1 — tự ẩn nếu rỗng, cùng nguyên tắc
     <x-frontend.banner-slot>/section-ocop. --}}
@if($sites->isNotEmpty())
<section class="py-10 border-t border-base-200">
    <div class="container">
        <div class="flex items-center justify-between mb-6">
            <h2 class="text-xl font-bold text-base-content">Di sản văn hóa {{ $province->name }}</h2>
            <a href="{{ route('heritage.public.index', ['province' => $province->province_code]) }}" class="text-sm font-semibold text-primary hover:underline">Xem tất cả →</a>
        </div>
        <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-6">
            @foreach($sites as $site)
            <a href="{{ route('heritage.public.show', ['slug' => $site->slug, 'id' => $site->id]) }}" class="group flex flex-col gap-2">
                <div class="aspect-[4/3] rounded-xl overflow-hidden bg-base-200">
                    <img src="{{ $site->getFirstMediaUrl('cover') ? $site->getFirstMediaUrl('cover', 'medium') : asset('images/post-cover-placeholder.svg') }}"
                         alt="{{ $site->name }}" class="h-full w-full object-cover" loading="lazy">
                </div>
                <div>
                    <h3 class="font-bold leading-snug group-hover:text-primary line-clamp-2">{{ $site->name }}</h3>
                    <p class="mt-1 text-xs text-base-content/60">
                        <span class="badge badge-sm badge-ghost">{{ $site->heritage_type->label() }}</span>
                        <span class="badge badge-sm badge-ghost">{{ $site->rank->label() }}</span>
                    </p>
                </div>
            </a>
            @endforeach
        </div>
    </div>
</section>
@endif
