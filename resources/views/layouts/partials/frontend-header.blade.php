@php($brand = config('app.name', 'Laravel') === 'Laravel' ? 'Cổng Thông Tin' : config('app.name'))
<header class="bg-base-100">
    <div class="max-w-6xl mx-auto px-4 py-4 flex items-center justify-between">
        <label for="portal-drawer" class="btn btn-ghost btn-square lg:hidden" aria-label="Mở menu">
            <svg class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h16" /></svg>
        </label>
        <a href="{{ route('post.public.home') }}" class="mx-auto lg:mx-0 flex items-center gap-2">
            <span class="inline-flex h-9 w-9 items-center justify-center rounded-full bg-primary text-primary-content font-black">{{ \Illuminate\Support\Str::of($brand)->substr(0, 2)->upper() }}</span>
            <span class="font-black text-2xl tracking-tight text-primary">{{ \Illuminate\Support\Str::upper($brand) }}</span>
        </a>
        <div class="w-10 lg:hidden"></div>
    </div>
</header>
