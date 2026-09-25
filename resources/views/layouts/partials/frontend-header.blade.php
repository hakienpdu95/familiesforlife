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
                <div class="col-12 col-lg-4">
                    <div class="header-logo">
                        <a href="{{ route('post.public.home') }}" title="{{ $brand }}">
                            <img src="{{ asset('images/logo.png') }}" alt="{{ $brand }}" class="img-fluid" width="100" height="100" />
                            <div class="inline-flex flex-col relative" style="left: -12px;">
                                <p class="logo first relative">
                                    <strong class="brand-name relative">
                                        <span class="br-1">Vì</span>
                                        <span class="br-2">Gia đình</span>
                                        <span class="br-3 absolute">.vn</span>
                                    </strong>
                                </p>
                                <p class="logo second relative">
                                    <strong class="brand-name p-0">
                                        <span class="br-">Trang tin tức tổng hợp</span>
                                    </strong>
                                </p>
                                <p class="logo third slg-actd relative">
                                    <span class="slogan-actd">Hạnh phúc cho mọi gia đình...</span>
                                </p>
                            </div>
                        </a>
                    </div>
                </div>

                <div class="col-12 col-lg-8">
                    <div class="text-right m-none">
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
                        <i class="fa-regular fa-house ic-home mr-1"></i>
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
