@php($brand = config('app.name', 'Laravel') === 'Laravel' ? 'Cổng Thông Tin' : config('app.name'))
{{-- Drawer di động — dùng lại chính $categories của navbar desktop (frontend-nav.blade.php),
     không lặp lại query hay hard-code mảng riêng. mobileSub state sống trong frontendNav
     (resources/js/frontend.js), tách biệt với subOpen của navbar desktop. --}}
<div class="drawer-side z-50">
    <label for="portal-drawer" aria-label="Đóng menu" class="drawer-overlay"></label>
    <div class="menu bg-base-100 min-h-full w-80 p-4 text-base-content">
        <a href="{{ route('post.public.home') }}" class="flex items-center gap-2 mb-6 px-1">
            <span class="inline-flex h-8 w-8 items-center justify-center rounded-full bg-primary text-primary-content font-black text-sm">{{ \Illuminate\Support\Str::of($brand)->substr(0, 2)->upper() }}</span>
            <span class="font-black text-lg text-primary">{{ \Illuminate\Support\Str::upper($brand) }}</span>
        </a>
        <ul class="flex flex-col gap-1">
            @foreach($categories ?? [] as $cat)
            <li>
                @if($cat->children->isNotEmpty())
                <a href="#" class="flex items-center justify-between font-semibold" @click.prevent="mobileSub = mobileSub === {{ $loop->index }} ? null : {{ $loop->index }}">
                    <span>{{ $cat->name }}</span>
                    <svg class="h-4 w-4 transition-transform" :class="mobileSub === {{ $loop->index }} ? 'rotate-180' : ''" viewBox="0 0 24 24" fill="none" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7" /></svg>
                </a>
                <ul x-show="mobileSub === {{ $loop->index }}" x-transition x-cloak class="pl-2">
                    <li><a href="{{ route('post.public.category', ['category' => $cat->slug]) }}">Tất cả {{ $cat->name }}</a></li>
                    @foreach($cat->children as $child)
                    <li><a href="{{ route('post.public.category', ['category' => $child->slug]) }}">{{ $child->name }}</a></li>
                    @endforeach
                </ul>
                @else
                <a href="{{ route('post.public.category', ['category' => $cat->slug]) }}" class="font-semibold">{{ $cat->name }}</a>
                @endif
            </li>
            @endforeach
        </ul>
    </div>
</div>
