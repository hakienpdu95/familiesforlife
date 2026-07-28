{{-- site-header — cấu trúc DOM + CSS copy 1:1 từ spec/header.html + spec/main.css (site tham
     khảo). Nội dung/thương hiệu bên trong là của familiesforlife: $menuTree (Modules/Menu, view
     composer ở MenuServiceProvider) thay cho danh mục/link của site tham khảo — xem
     Modules\Menu\Database\Seeders\MenuDatabaseSeeder.
     3 phần spec KHÔNG cung cấp (chỉ HTML+CSS tĩnh, không có JS/font gốc):
       - glyph icon-* thật (spec chỉ @import 1 file fontello.css không kèm theo) → CSS mask tự vẽ.
       - JS gắn .is-active/.is-open/.is-pinned khi bấm nút/cuộn trang → thay bằng Alpine (state
         dùng chung frontendNav, resources/js/frontend.js — initHeaderPin() port lại 1:1
         windowScroll() ở spec/app.js) — phần CSS phản ứng các class này đã copy y hệt
         spec/main.css trong resources/css/frontend.css. --}}
@php($brand = config('app.site_name'))
<header class="site-header" id="site-header"
        :class="pinned ? 'is-pinned' : ''"
        :style="pinned ? ('height: ' + headerHeight + 'px') : ''"
        x-init="initHeaderPin($el)">
    <div class="container">
        <div class="site-header__topbar">
            <div class="links" :class="search ? 'is-active' : ''" @click.outside="search = false">
                <a href="#" title="Theo dõi chúng tôi trên Facebook" rel="nofollow">
                    <i class="icon-facebook"></i>
                </a>
                <a href="{{ route('post.public.sitemap') }}" title="RSS">
                    <i class="icon-rss"></i>
                </a>
                <a href="#" title="Tìm kiếm" id="searchDesktop" :class="search ? 'is-active' : ''" @click.prevent="search = !search">
                    <i class="icon-search"></i>
                    <i class="icon-times"></i>
                </a>
                <div class="input-wrap">
                    <form method="GET" action="{{ url()->current() }}">
                        <input type="text" class="form-control" placeholder="Tìm kiếm ..." id="txtSearchTwo" name="q" value="{{ $search ?? '' }}">
                        <button type="submit" class="icon-search btnSearch" style="border: 0; background: transparent;" aria-label="Tìm kiếm"></button>
                    </form>
                </div>
            </div>
        </div>

        <div class="site-header__toolbar">
            <span class="btn-search m-btn" role="button" tabindex="0" aria-label="Tìm kiếm" @click="search = !search">
                <i class="icon-search"></i>
            </span>
            <span class="btn-expand m-btn" role="button" tabindex="0" aria-label="Mở menu" :class="mobileNavOpen ? 'is-active' : ''" @click="mobileNavOpen = !mobileNavOpen">
                <i class="icon-bars"></i>
                <i class="icon-times"></i>
            </span>
        </div>

        <div class="site-header__content">
            <div class="row">
                <div class="col-12 col-lg-3">
                    <h3 class="logo">
                        <a href="{{ route('post.public.home') }}" title="{{ $brand }}">
                            <span class="logo__mark">{{ \Illuminate\Support\Str::of($brand)->substr(0, 2)->upper() }}</span>
                            <span class="logo__text">{{ \Illuminate\Support\Str::upper($brand) }}</span>
                        </a>
                    </h3>
                </div>

                <div class="col-12 col-lg-9">
                    <div class="text-right m-none">
                        {{-- spec/Banner_Management_Technical_Specification.md §7.2 — ô quảng cáo cạnh
                             logo (header_ad), quản lý qua dashboard/banners. Không có ngữ cảnh category
                             ở đây (§2) nên không truyền :context — luôn chỉ nhận banner "Toàn site". --}}
                        <x-frontend.banner-slot placement="header_ad" />
                    </div>
                </div>
            </div>
        </div>

        <nav>
            <ul class="nav" :class="mobileNavOpen ? 'is-open' : ''">
                <li class="nav-item nav-search">
                    <form method="GET" action="{{ url()->current() }}" class="input-wrap">
                        <input id="txtSearchOne" type="text" name="q" class="form-control" placeholder="Từ khóa" value="{{ $search ?? '' }}">
                        <button type="submit" style="border: 0; background: transparent;" class="icon icon-search btnSearch" aria-label="Tìm kiếm"></button>
                    </form>
                </li>
                <li class="nav-item">
                    <a class="nav-link" href="{{ route('post.public.home') }}" title="Trang chủ">
                        <i class="icon icon-home mr-1"></i>
                    </a>
                </li>
                @foreach($menuTree ?? [] as $item)
                @php($hasChildren = $item->children->isNotEmpty())
                @php($url = $item->resolveUrl())
                <li class="nav-item{{ $url && $url === url()->current() ? ' active' : '' }}">
                    <a class="nav-link"
                       href="{{ $url ?? 'javascript:;' }}"
                       title="{{ $item->label }}"
                       @if($item->open_in_new_tab) target="_blank" @endif
                       @if($item->open_in_new_tab || $item->isExternalUrl())
                       rel="{{ trim(($item->open_in_new_tab ? 'noopener ' : '') . ($item->isExternalUrl() ? 'nofollow' : '')) }}"
                       @endif
                    >
                        @if($item->icon)<i class="{{ $item->icon }} mr-1"></i>@endif{{ $item->label }}
                    </a>

                    @if($hasChildren)
                    <ul class="nav-sub">
                        @foreach($item->children as $child)
                        @php($childUrl = $child->resolveUrl())
                        @php($hasGrandchildren = $child->children->isNotEmpty())
                        <li class="nav-item">
                            <a class="nav-link" href="{{ $childUrl ?? '#' }}" title="{{ $child->label }}"
                               @if($child->open_in_new_tab) target="_blank" @endif
                               @if($child->open_in_new_tab || $child->isExternalUrl())
                               rel="{{ trim(($child->open_in_new_tab ? 'noopener ' : '') . ($child->isExternalUrl() ? 'nofollow' : '')) }}"
                               @endif
                            >
                                @if($child->icon)<i class="{{ $child->icon }} mr-1"></i>@endif{{ $child->label }}
                            </a>

                            {{-- Cấp 3 (VD "Babies"/"Toddler & Kids" > giai đoạn tuổi > mục lá, xem
                                 MenuDatabaseSeeder::seedNestedUrlGroup()) — hiện flyout sang phải
                                 khi hover đúng <li> giai đoạn tuổi này (xem .nav-sub .nav-sub trong
                                 frontend.css), không phải khi hover cả nhóm cấp 1. --}}
                            @if($hasGrandchildren)
                            <ul class="nav-sub">
                                @foreach($child->children as $grandchild)
                                @php($grandchildUrl = $grandchild->resolveUrl())
                                <li class="nav-item">
                                    <a class="nav-link" href="{{ $grandchildUrl ?? '#' }}" title="{{ $grandchild->label }}"
                                       @if($grandchild->open_in_new_tab) target="_blank" @endif
                                       @if($grandchild->open_in_new_tab || $grandchild->isExternalUrl())
                                       rel="{{ trim(($grandchild->open_in_new_tab ? 'noopener ' : '') . ($grandchild->isExternalUrl() ? 'nofollow' : '')) }}"
                                       @endif
                                    >
                                        @if($grandchild->icon)<i class="{{ $grandchild->icon }} mr-1"></i>@endif{{ $grandchild->label }}
                                    </a>
                                </li>
                                @endforeach
                            </ul>
                            @endif
                        </li>
                        @endforeach
                    </ul>
                    @endif
                </li>
                @endforeach
            </ul>
        </nav>
    </div>
</header>
