{{-- footer — cấu trúc DOM copy 1:1 từ spec/footer.html (site tham khảo), màu sắc/bố cục theo
     spec/footer.png (không có stylesheet gốc đi kèm lần này, chỉ HTML + ảnh chụp — xem CSS
     tương ứng trong resources/css/frontend.css, ghi rõ đây là bản tự viết theo ảnh tham khảo).
     Nội dung là của familiesforlife (không dùng nhãn/link "Motherly" của site tham khảo).

     $footerMenuTree = MenuItem::tree('footer') — GỌI GIỐNG HỆT cơ chế $menuTree ở header (view
     composer chung, Modules\Menu\Providers\MenuServiceProvider), seed ở
     Modules\Menu\Database\Seeders\MenuDatabaseSeeder::seedFooterGroup(). Quy ước: mỗi nhóm gốc
     (link_type=none) là 1 cột ".nav-footer__content" (tiêu đề = label, link con = children) —
     TRỪ nhóm CUỐI CÙNG (sort_order lớn nhất, vd "Pháp lý") render thành thanh link pháp lý
     cuối trang ".nav-siteinfo" thay vì 1 cột, khớp đúng spec/footer.html (nav-footer__content
     ở trên vs nav-siteinfo ở dưới cùng dòng bản quyền). --}}
@php
    $brand = config('app.site_name');

    $footerColumns = ($footerMenuTree ?? collect())->filter(fn ($item) => $item->children->isNotEmpty());
    $legalGroup    = $footerColumns->last();
    $footerColumns = $footerColumns->count() > 1 ? $footerColumns->slice(0, -1) : collect();
@endphp
<footer class="footer js-footer">
    <div class="container">
        <div class="social-icons">
            <h4 class="social-icons__title">Chúng tôi tin vào một thế giới nơi mọi gia đình đều được yêu thương và phát triển trọn vẹn</h4>
            <ul class="social-icons-list">
                <li class="social-icons-list__icon social-icons-list__icon--tiktok">
                    <a class="social-icons-list__link" href="#" target="_blank" rel="noopener" aria-label="TikTok">
                        <span class="tiktok"></span>
                    </a>
                </li>
                <li class="social-icons-list__icon social-icons-list__icon--pinterest">
                    <a class="social-icons-list__link" href="#" target="_blank" rel="noopener" aria-label="Pinterest">
                        <span class="pinterest"></span>
                    </a>
                </li>
                <li class="social-icons-list__icon social-icons-list__icon--facebook">
                    <a class="social-icons-list__link" href="#" target="_blank" rel="noopener" aria-label="Facebook">
                        <span class="facebook"></span>
                    </a>
                </li>
                <li class="social-icons-list__icon social-icons-list__icon--instagram">
                    <a class="social-icons-list__link" href="#" target="_blank" rel="noopener" aria-label="Instagram">
                        <span class="instagram"></span>
                    </a>
                </li>
                <li class="social-icons-list__icon social-icons-list__icon--youtube">
                    <a class="social-icons-list__link" href="#" target="_blank" rel="noopener" aria-label="YouTube">
                        <span class="youtube"></span>
                    </a>
                </li>
            </ul>
        </div>

        @foreach($footerColumns as $group)
        <div class="nav-footer__content">
            <h4 class="nav-footer__title">{{ $group->label }}</h4>
            <div class="menu-footer-container">
                <ul class="nav-footer__list">
                    @foreach($group->children as $link)
                    <li class="menu-item">
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
        </div>
        @endforeach
    </div>

    <div class="footer-container container">
        <div class="copyright-siteinfo">
            @if($legalGroup)
            <div class="nav-siteinfo">
                <ul class="nav-siteinfo__list">
                    @foreach($legalGroup->children as $link)
                    <li class="menu-item">
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
            @endif

            <div class="copyright">
                <p>© {{ now()->year }} {{ $brand }}. Bảo lưu mọi quyền.</p>
            </div>
        </div>
    </div>
</footer>
