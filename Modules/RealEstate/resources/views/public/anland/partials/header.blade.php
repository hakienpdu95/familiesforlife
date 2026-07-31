{{-- Header riêng của Anland — không include layouts.partials.frontend-header (theme/nav khác
     hẳn trang chủ familiesforlife). Nav chỉ trỏ tới trang thật đã có (Trang chủ/Mua bán/Cho
     thuê); "Dự án"/"Tin tức thị trường" hiển thị nhưng khoá lại (chưa có trang) thay vì dẫn
     tới link chết. --}}
<header class="anland-header sticky top-0 z-50" x-data="anlandNav">
    <div class="anland-container flex items-center justify-between gap-4 py-3">
        <a href="{{ route('real-estate.public.anland.home') }}" class="flex items-center gap-2 shrink-0">
            <span class="anland-header__brand-mark w-9 h-9 rounded-lg flex items-center justify-center font-bold">A</span>
            <span class="text-lg font-bold tracking-tight">Anland</span>
        </a>

        <nav class="hidden lg:flex items-center gap-1">
            <a href="{{ route('real-estate.public.anland.home') }}" class="btn btn-ghost btn-sm text-neutral-content">Trang chủ</a>
            <a href="{{ route('real-estate.public.sale.index') }}" class="btn btn-ghost btn-sm text-neutral-content">Mua bán</a>
            <a href="{{ route('real-estate.public.rent.index') }}" class="btn btn-ghost btn-sm text-neutral-content">Cho thuê</a>
            <span class="anland-header__nav-link is-disabled btn btn-ghost btn-sm text-neutral-content" title="Sắp ra mắt">
                Dự án <span class="badge badge-xs badge-accent ml-1">Sắp ra mắt</span>
            </span>
            <span class="anland-header__nav-link is-disabled btn btn-ghost btn-sm text-neutral-content" title="Sắp ra mắt">
                Tin tức thị trường <span class="badge badge-xs badge-accent ml-1">Sắp ra mắt</span>
            </span>
        </nav>

        <div class="hidden lg:flex items-center gap-2">
            @auth
                <a href="{{ route('backend.real-estate.create') }}" class="btn btn-accent btn-sm">Đăng tin ngay</a>
            @else
                <a href="{{ route('login') }}" class="btn btn-ghost btn-sm text-neutral-content">Đăng nhập</a>
                <a href="{{ route('login') }}" class="btn btn-accent btn-sm">Đăng tin ngay</a>
            @endauth
        </div>

        <button type="button" class="btn btn-ghost btn-sm lg:hidden text-neutral-content" @click="mobileNavOpen = !mobileNavOpen" aria-label="Mở menu">
            <svg xmlns="http://www.w3.org/2000/svg" class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                <path stroke-linecap="round" stroke-linejoin="round" d="M4 6h16M4 12h16M4 18h16" />
            </svg>
        </button>
    </div>

    <div class="lg:hidden border-t border-white/10" x-show="mobileNavOpen" x-cloak x-transition>
        <div class="anland-container flex flex-col py-3 gap-1">
            <a href="{{ route('real-estate.public.anland.home') }}" class="btn btn-ghost btn-sm justify-start text-neutral-content">Trang chủ</a>
            <a href="{{ route('real-estate.public.sale.index') }}" class="btn btn-ghost btn-sm justify-start text-neutral-content">Mua bán</a>
            <a href="{{ route('real-estate.public.rent.index') }}" class="btn btn-ghost btn-sm justify-start text-neutral-content">Cho thuê</a>
            @auth
                <a href="{{ route('backend.real-estate.create') }}" class="btn btn-accent btn-sm mt-2">Đăng tin ngay</a>
            @else
                <a href="{{ route('login') }}" class="btn btn-accent btn-sm mt-2">Đăng tin ngay</a>
            @endauth
        </div>
    </div>
</header>
