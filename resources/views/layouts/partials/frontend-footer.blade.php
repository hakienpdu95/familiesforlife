@php($brand = config('app.name', 'Laravel') === 'Laravel' ? 'Cổng Thông Tin' : config('app.name'))
{{-- $footerMenuTree = MenuItem::tree('footer') (Modules/Menu, view composer ở
     MenuServiceProvider) — spec/Menu_Navigation_Technical_Specification.md §7.5: 1 đoạn
     blade phục vụ cả 2 trường hợp (Admin chưa cấu hình gì cho footer → chỉ hiện "Trang Chủ" +
     bản quyền như trước; Admin thêm mục → tự thành cột/link phẳng tuỳ mục đó có children hay
     không, KHÔNG cần field/cấu hình riêng để phân biệt "chế độ"). --}}
<footer class="bg-neutral text-neutral-content/80 mt-auto">
    <div class="max-w-6xl mx-auto px-4 py-6">
        @if(($footerMenuTree ?? collect())->isNotEmpty())
        <div class="grid grid-cols-1 sm:grid-cols-{{ min($footerMenuTree->count() + 1, 4) }} gap-6 mb-4 text-xs">
            <div>
                <a href="{{ route('post.public.home') }}" class="font-semibold hover:text-white">Trang Chủ</a>
            </div>
            @foreach($footerMenuTree as $item)
                @if($item->children->isNotEmpty())
                <div>
                    <h3 class="font-bold uppercase tracking-wide mb-2 text-neutral-content">{{ $item->label }}</h3>
                    <ul class="space-y-1.5">
                        @foreach($item->children as $link)
                        <li>
                            <a href="{{ $link->resolveUrl() ?? '#' }}"
                               @if($link->open_in_new_tab) target="_blank" @endif
                               @if($link->open_in_new_tab || $link->isExternalUrl())
                               rel="{{ trim(($link->open_in_new_tab ? 'noopener ' : '') . ($link->isExternalUrl() ? 'nofollow' : '')) }}"
                               @endif
                               class="hover:text-white">{{ $link->label }}</a>
                        </li>
                        @endforeach
                    </ul>
                </div>
                @else
                <div>
                    <a href="{{ $item->resolveUrl() ?? '#' }}"
                       @if($item->open_in_new_tab) target="_blank" @endif
                       @if($item->open_in_new_tab || $item->isExternalUrl())
                       rel="{{ trim(($item->open_in_new_tab ? 'noopener ' : '') . ($item->isExternalUrl() ? 'nofollow' : '')) }}"
                       @endif
                       class="font-semibold hover:text-white">{{ $item->label }}</a>
                </div>
                @endif
            @endforeach
        </div>
        <div class="pt-4 border-t border-neutral-content/10 text-xs">
            <span class="font-semibold">© {{ now()->year }} {{ $brand }}. Bảo lưu mọi quyền.</span>
        </div>
        @else
        <div class="flex flex-col sm:flex-row items-center justify-between gap-4 text-xs">
            <span class="font-semibold">© {{ now()->year }} {{ $brand }}. Bảo lưu mọi quyền.</span>
            <a href="{{ route('post.public.home') }}" class="font-semibold hover:text-white">Trang Chủ</a>
        </div>
        @endif
    </div>
</footer>
