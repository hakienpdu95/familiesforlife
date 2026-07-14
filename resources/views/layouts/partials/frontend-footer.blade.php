@php($brand = config('app.name', 'Laravel') === 'Laravel' ? 'Cổng Thông Tin' : config('app.name'))
<footer class="bg-neutral text-neutral-content/80 mt-auto">
    <div class="max-w-6xl mx-auto px-4 py-6 flex flex-col sm:flex-row items-center justify-between gap-4 text-xs">
        <span class="font-semibold">© {{ now()->year }} {{ $brand }}. Bảo lưu mọi quyền.</span>
        <ul class="flex gap-5 font-semibold">
            <li><a href="{{ route('post.public.home', ['locale' => $locale ?? config('post.default_locale')]) }}" class="hover:text-white">Trang Chủ</a></li>
            @foreach(($categories ?? collect())->take(4) as $cat)
            <li><a href="{{ route('post.public.category', ['locale' => $locale ?? config('post.default_locale'), 'category' => $cat->slug]) }}" class="hover:text-white">{{ $cat->name }}</a></li>
            @endforeach
        </ul>
    </div>
</footer>
