{{-- Mega-menu 3 cấp — dữ liệu thật từ MenuItem::tree('header') (Modules/Menu, view composer
     ở MenuServiceProvider), KHÔNG còn PostCategory::navTree()/hard-code "Sự Kiện" nữa (đã
     chuyển thành 1 MenuItem link_type=url qua menu:backfill-from-categories — xem
     spec/Menu_Navigation_Technical_Specification.md §7.6). Render server-side (Blade @foreach)
     để crawler/SEO thấy ngay trong HTML; Alpine chỉ điều khiển mở/đóng dropdown/flyout
     (subOpen/flyOpen, xem resources/js/frontend.js) + phím tắt (§7.2.1). --}}
@if(($menuTree ?? collect())->isNotEmpty())
<nav class="bg-primary sticky top-0 z-40 shadow-md hidden lg:block">
    <div class="max-w-6xl mx-auto px-4">
        <ul role="menubar" class="menu menu-horizontal gap-1 text-xs font-bold uppercase tracking-wide px-0 justify-center w-full">
            @foreach($menuTree as $item)
            @php($hasChildren = $item->children->isNotEmpty())
            <li role="none" class="relative" @if($hasChildren) @mouseleave="subOpen === {{ $loop->index }} ? subOpen = null : null" @endif>
                <a role="menuitem"
                   href="{{ $item->resolveUrl() ?? '#' }}"
                   @if($item->open_in_new_tab) target="_blank" @endif
                   @if($item->open_in_new_tab || $item->isExternalUrl())
                   rel="{{ trim(($item->open_in_new_tab ? 'noopener ' : '') . ($item->isExternalUrl() ? 'nofollow' : '')) }}"
                   @endif
                   class="rounded-none py-4 text-primary-content"
                   @if($hasChildren)
                   aria-haspopup="true"
                   :aria-expanded="(subOpen === {{ $loop->index }}).toString()"
                   @click.prevent="subOpen = subOpen === {{ $loop->index }} ? null : {{ $loop->index }}"
                   @keydown.enter.prevent="subOpen = {{ $loop->index }}; $nextTick(() => $el.parentElement.querySelector('[role=menu] a')?.focus())"
                   @keydown.down.prevent="subOpen = {{ $loop->index }}; $nextTick(() => $el.parentElement.querySelector('[role=menu] a')?.focus())"
                   :class="subOpen === {{ $loop->index }} ? 'bg-black/10 text-warning' : ''"
                   @endif
                >
                    @if($item->icon)<i class="{{ $item->icon }} mr-1"></i>@endif{{ $item->label }}
                </a>

                @if($hasChildren)
                {{-- <ul>, không phải <div>: selector component .menu của daisyui nhắm
                     `li > :not(ul,details,.menu-title,.btn)` để tự set display:grid cho item
                     ngang hàng — dùng <div> ở đây bị dính chọn nhầm, vỡ layout item con. --}}
                <ul role="menu" x-show="subOpen === {{ $loop->index }}" x-transition x-cloak
                    @click.outside="subOpen = null"
                    @keydown.escape="subOpen = null; $el.previousElementSibling?.focus()"
                    class="menu menu-sm absolute left-0 top-full w-56 flex-nowrap bg-base-100 text-base-content rounded-b-lg shadow-lg z-50 normal-case">
                    @foreach($item->children as $child)
                    @php($childHasFlyout = $child->children->isNotEmpty())
                    <li role="none" class="relative"
                        @if($childHasFlyout)
                        @mouseenter="flyOpen = '{{ $loop->parent->index }}_{{ $loop->index }}'"
                        @mouseleave="flyOpen = null"
                        @endif>
                        <a role="menuitem"
                           href="{{ $child->resolveUrl() ?? '#' }}"
                           @if($child->open_in_new_tab) target="_blank" @endif
                           @if($child->open_in_new_tab || $child->isExternalUrl())
                           rel="{{ trim(($child->open_in_new_tab ? 'noopener ' : '') . ($child->isExternalUrl() ? 'nofollow' : '')) }}"
                           @endif
                           class="hover:text-primary"
                           @if($childHasFlyout)
                           aria-haspopup="true"
                           :aria-expanded="(flyOpen === '{{ $loop->parent->index }}_{{ $loop->index }}').toString()"
                           @keydown.right.prevent="flyOpen = '{{ $loop->parent->index }}_{{ $loop->index }}'; $nextTick(() => $el.parentElement.querySelector('[role=menu] a')?.focus())"
                           @endif
                        >
                            @if($child->icon)<i class="{{ $child->icon }} mr-1"></i>@endif{{ $child->label }}
                        </a>

                        @if($childHasFlyout)
                        {{-- Flyout cấp 3, định vị bên phải mục cấp 2 — hành vi hover, KHÔNG
                             click-toggle (khác cấp 1/2), đúng chuẩn UX mega-menu tham khảo
                             (spec §7.3: di chuột qua "CELEBRATE" thấy ngay flyout). --}}
                        <ul role="menu" x-show="flyOpen === '{{ $loop->parent->index }}_{{ $loop->index }}'" x-transition x-cloak
                            @keydown.escape="flyOpen = null; $el.previousElementSibling?.focus()"
                            class="menu menu-sm absolute left-full top-0 w-56 flex-nowrap bg-base-100 text-base-content rounded-lg shadow-lg z-50">
                            @foreach($child->children as $grandchild)
                            <li role="none">
                                <a role="menuitem"
                                   href="{{ $grandchild->resolveUrl() ?? '#' }}"
                                   @if($grandchild->open_in_new_tab) target="_blank" @endif
                                   @if($grandchild->open_in_new_tab || $grandchild->isExternalUrl())
                                   rel="{{ trim(($grandchild->open_in_new_tab ? 'noopener ' : '') . ($grandchild->isExternalUrl() ? 'nofollow' : '')) }}"
                                   @endif
                                   class="hover:text-primary"
                                >{{ $grandchild->label }}</a>
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
    </div>
</nav>
@endif
