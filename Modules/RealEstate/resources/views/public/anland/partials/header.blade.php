{{-- Header riêng của Anland — không include layouts.partials.frontend-header (theme/nav khác
     hẳn trang chủ familiesforlife). Nav chỉ trỏ tới trang thật đã có (Trang chủ/Mua bán/Cho
     thuê); "Dự án"/"Tin tức thị trường" hiển thị nhưng khoá lại (chưa có trang) thay vì dẫn
     tới link chết. Giao diện chuyển thể từ spec/thu-vien-nha-dat.html — header nền trắng,
     logo dạng khiên, dòng cam kết nhỏ màu đỏ bên dưới nav. --}}
<header class="anland-header sticky top-0 z-50" x-data="anlandNav">
    <div class="anland-container flex items-center justify-between gap-4 h-[76px]">
        <a href="{{ route('real-estate.public.anland.home') }}" class="flex items-center gap-2.5 shrink-0">
            <span class="anland-header__brand-mark w-10 h-10 rounded-full flex items-center justify-center shrink-0">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.7" stroke-linecap="round" stroke-linejoin="round" class="w-5 h-5"><path d="M4 21V8l8-5 8 5v13"/><path d="M9 21v-6h6v6"/><path d="M9 12h.01M15 12h.01M12 8h.01"/></svg>
            </span>
            <span class="leading-tight">
                <span class="block text-lg font-bold tracking-tight">
                    <span style="color:var(--az-navy)">An</span><span style="color:var(--az-green)">land</span>
                </span>
                <span class="block text-[11px] text-base-content/50 -mt-1">Kênh thông tin nhà đất</span>
            </span>
        </a>

        <nav class="hidden lg:flex items-center gap-1">
            <a href="{{ route('real-estate.public.anland.home') }}" class="btn btn-ghost btn-sm">Trang chủ</a>
            <a href="{{ route('real-estate.public.sale.index') }}" class="btn btn-ghost btn-sm">Mua bán</a>
            <a href="{{ route('real-estate.public.rent.index') }}" class="btn btn-ghost btn-sm">Cho thuê</a>
            <span class="anland-header__nav-link is-disabled btn btn-ghost btn-sm" title="Sắp ra mắt">
                Dự án <span class="badge badge-xs badge-accent ml-1">Sắp ra mắt</span>
            </span>
            <span class="anland-header__nav-link is-disabled btn btn-ghost btn-sm" title="Sắp ra mắt">
                Tin tức thị trường <span class="badge badge-xs badge-accent ml-1">Sắp ra mắt</span>
            </span>
        </nav>

        <div class="hidden lg:flex items-center gap-2">
            @auth
                <a href="{{ route('backend.real-estate.create') }}" class="btn btn-accent btn-sm rounded-full px-5">Đăng tin ngay</a>
            @else
                <a href="{{ route('login') }}" class="btn btn-ghost btn-sm">Đăng nhập</a>
                <a href="{{ route('login') }}" class="btn btn-accent btn-sm rounded-full px-5">Đăng tin ngay</a>
            @endauth
        </div>

        <button type="button" class="btn btn-ghost btn-sm lg:hidden" @click="mobileNavOpen = !mobileNavOpen" aria-label="Mở menu">
            <svg xmlns="http://www.w3.org/2000/svg" class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                <path stroke-linecap="round" stroke-linejoin="round" d="M4 6h16M4 12h16M4 18h16" />
            </svg>
        </button>
    </div>

    <div class="lg:hidden border-t border-base-300" x-show="mobileNavOpen" x-cloak x-transition>
        <div class="anland-container flex flex-col py-3 gap-1">
            <a href="{{ route('real-estate.public.anland.home') }}" class="btn btn-ghost btn-sm justify-start">Trang chủ</a>
            <a href="{{ route('real-estate.public.sale.index') }}" class="btn btn-ghost btn-sm justify-start">Mua bán</a>
            <a href="{{ route('real-estate.public.rent.index') }}" class="btn btn-ghost btn-sm justify-start">Cho thuê</a>
            @auth
                <a href="{{ route('backend.real-estate.create') }}" class="btn btn-accent btn-sm rounded-full mt-2">Đăng tin ngay</a>
            @else
                <a href="{{ route('login') }}" class="btn btn-ghost btn-sm justify-start">Đăng nhập</a>
                <a href="{{ route('login') }}" class="btn btn-accent btn-sm rounded-full mt-2">Đăng tin ngay</a>
            @endauth
        </div>
    </div>

    <div class="anland-header__tagline hidden sm:block">
        <p class="anland-container py-2 text-[13px] italic font-medium">
            ...tin đăng đã kiểm duyệt nội dung, minh bạch pháp lý, an tâm giao dịch...
        </p>
    </div>
</header>
