{{-- spec/Province_Showcase_Technical_Specification.md §5 — tự ẩn nếu rỗng, cùng nguyên tắc
     <x-frontend.banner-slot>. --}}
@if($translations->isNotEmpty())
<section class="py-10 border-t border-base-200">
    <div class="container">
        <div class="flex items-center justify-between mb-6">
            <h2 class="text-xl font-bold text-base-content">Di sản văn hóa {{ $province->name }}</h2>
            <a href="{{ route('post.public.category', ['category' => 'di-san-van-hoa']) }}" class="text-sm font-semibold text-primary hover:underline">Xem tất cả →</a>
        </div>
        <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-6">
            @foreach($translations as $translation)
            <x-frontend.article-card :translation="$translation" size="md" />
            @endforeach
        </div>
    </div>
</section>
@endif
