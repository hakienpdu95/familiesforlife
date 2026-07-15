@if($translations->isNotEmpty())
<section class="py-10 border-t border-base-200 bg-base-200/30">
    <div class="container">
        <div class="flex items-center justify-between mb-6">
            <h2 class="text-xl font-bold text-base-content">Ẩm thực {{ $province->name }}</h2>
            <a href="{{ route('post.public.category', ['category' => 'am-thuc-vung-mien']) }}" class="text-sm font-semibold text-primary hover:underline">Xem tất cả →</a>
        </div>
        <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-6">
            @foreach($translations as $translation)
            <x-frontend.article-card :translation="$translation" size="md" />
            @endforeach
        </div>
    </div>
</section>
@endif
