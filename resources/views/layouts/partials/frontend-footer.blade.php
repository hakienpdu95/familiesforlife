@php
    $brand = config('app.site_name');

    $footerColumns = ($footerMenuTree ?? collect())->filter(fn ($item) => $item->children->isNotEmpty());
    $legalGroup    = $footerColumns->last();
    $footerColumns = $footerColumns->count() > 1 ? $footerColumns->slice(0, -1) : collect();

    $socials = [
        'facebook'  => 'Facebook',
        'youtube'   => 'YouTube',
        'tiktok'    => 'TikTok',
        'instagram' => 'Instagram',
        'pinterest' => 'Pinterest',
    ];
@endphp
<footer class="footer js-footer">
    <div class="container footer-main">
        <div class="footer-brand">
            <a href="{{ route('post.public.home') }}" class="footer-brand__logo" title="{{ $brand }}">
                <img src="{{ asset('images/logo.png') }}" alt="" width="44" height="44" loading="lazy" decoding="async">
                <span class="footer-brand__name">{{ $brand }}</span>
            </a>
            <p class="footer-brand__tagline">{{ __('Chúng tôi tin vào một thế giới nơi mọi gia đình đều được yêu thương và phát triển trọn vẹn.') }}</p>

            <ul class="social-icons-list" aria-label="{{ __('Mạng xã hội') }}">
                @foreach($socials as $key => $label)
                <li>
                    <a class="social-icons-list__link" href="#" target="_blank" rel="noopener" aria-label="{{ $label }}" title="{{ $label }}">
                        <span class="{{ $key }}" aria-hidden="true"></span>
                    </a>
                </li>
                @endforeach
            </ul>
        </div>

        @if($footerColumns->isNotEmpty())
        <nav class="footer-nav" aria-label="{{ __('Liên kết chân trang') }}">
            @foreach($footerColumns as $group)
            <div class="nav-footer__content">
                <h4 class="nav-footer__title">{{ $group->label }}</h4>
                <ul class="nav-footer__list">
                    @foreach($group->children as $link)
                    <li>
                        <a href="{{ $link->resolveUrl() ?? '#' }}"
                           @if($link->open_in_new_tab) target="_blank" @endif
                           @if($link->open_in_new_tab || $link->isExternalUrl())
                           rel="{{ trim(($link->open_in_new_tab ? 'noopener ' : '') . ($link->isExternalUrl() ? 'nofollow' : '')) }}"
                           @endif
                        >{{ $link->label }}</a>
                    </li>
                    @endforeach
                </ul>
            </div>
            @endforeach
        </nav>
        @endif
    </div>

    <div class="container footer-bottom">
        <p class="copyright">{{ __('© :year :brand. Bảo lưu mọi quyền.', ['year' => now()->year, 'brand' => $brand]) }}</p>

        <div class="footer-bottom__end">
            @if($legalGroup)
            <ul class="nav-siteinfo__list">
                @foreach($legalGroup->children as $link)
                <li>
                    <a href="{{ $link->resolveUrl() ?? '#' }}"
                       @if($link->open_in_new_tab) target="_blank" @endif
                       @if($link->open_in_new_tab || $link->isExternalUrl())
                       rel="{{ trim(($link->open_in_new_tab ? 'noopener ' : '') . ($link->isExternalUrl() ? 'nofollow' : '')) }}"
                       @endif
                    >{{ $link->label }}</a>
                </li>
                @endforeach
            </ul>
            @endif

            <button type="button" class="footer-totop" x-data @click="window.scrollTo({ top: 0, behavior: 'smooth' })">
                {{ __('Lên đầu trang') }}
                <svg viewBox="0 0 24 24" width="14" height="14" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M12 19V5M6 11l6-6 6 6"/></svg>
            </button>
        </div>
    </div>
</footer>
