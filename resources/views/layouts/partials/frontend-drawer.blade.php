@php($brand = config('app.name', 'Laravel') === 'Laravel' ? 'Cổng Thông Tin' : config('app.name'))
{{-- Drawer di động — dùng $menuTree (Modules/Menu, view composer ở MenuServiceProvider),
     KHÔNG còn PostCategory::navTree() nữa (xem frontend-nav.blade.php). Accordion 3 cấp:
     mobileSub (cấp 2) + mobileFlySub (cấp 3) sống trong frontendNav
     (resources/js/frontend.js), tách biệt với subOpen/flyOpen của navbar desktop — 2 tầng
     UI (desktop hover-flyout, mobile click-accordion) tồn tại song song trong DOM, ẩn/hiện
     bằng CSS breakpoint (hidden lg:block), không phải điều kiện PHP. --}}
<div class="drawer-side z-50">
    <label for="portal-drawer" aria-label="Đóng menu" class="drawer-overlay"></label>
    <div class="menu bg-base-100 min-h-full w-80 p-4 text-base-content">
        <a href="{{ route('post.public.home') }}" class="flex items-center gap-2 mb-6 px-1">
            <span class="inline-flex h-8 w-8 items-center justify-center rounded-full bg-primary text-primary-content font-black text-sm">{{ \Illuminate\Support\Str::of($brand)->substr(0, 2)->upper() }}</span>
            <span class="font-black text-lg text-primary">{{ \Illuminate\Support\Str::upper($brand) }}</span>
        </a>
        <ul class="flex flex-col gap-1">
            @foreach($menuTree ?? [] as $item)
            @php($hasChildren = $item->children->isNotEmpty())
            @php($url = $item->resolveUrl())
            <li>
                <div class="flex items-center justify-between gap-1">
                    @if($url)
                    <a href="{{ $url }}"
                       @if($item->open_in_new_tab) target="_blank" @endif
                       @if($item->open_in_new_tab || $item->isExternalUrl())
                       rel="{{ trim(($item->open_in_new_tab ? 'noopener ' : '') . ($item->isExternalUrl() ? 'nofollow' : '')) }}"
                       @endif
                       class="font-semibold flex-1 py-1">
                        @if($item->icon)<i class="{{ $item->icon }} mr-1"></i>@endif{{ $item->label }}
                    </a>
                    @else
                    <span class="font-semibold flex-1 py-1 text-base-content/70">
                        @if($item->icon)<i class="{{ $item->icon }} mr-1"></i>@endif{{ $item->label }}
                    </span>
                    @endif

                    @if($hasChildren)
                    <button type="button" class="btn btn-ghost btn-xs btn-square shrink-0"
                            aria-haspopup="true" :aria-expanded="(mobileSub === {{ $loop->index }}).toString()"
                            @click="mobileSub = mobileSub === {{ $loop->index }} ? null : {{ $loop->index }}">
                        <svg class="h-4 w-4 transition-transform" :class="mobileSub === {{ $loop->index }} ? 'rotate-180' : ''" viewBox="0 0 24 24" fill="none" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7" /></svg>
                    </button>
                    @endif
                </div>

                @if($hasChildren)
                <ul x-show="mobileSub === {{ $loop->index }}" x-transition x-cloak class="pl-2">
                    @foreach($item->children as $child)
                    @php($childHasFlyout = $child->children->isNotEmpty())
                    @php($childUrl = $child->resolveUrl())
                    @php($flyKey = $loop->parent->index . '_' . $loop->index)
                    <li>
                        <div class="flex items-center justify-between gap-1">
                            @if($childUrl)
                            <a href="{{ $childUrl }}"
                               @if($child->open_in_new_tab) target="_blank" @endif
                               @if($child->open_in_new_tab || $child->isExternalUrl())
                               rel="{{ trim(($child->open_in_new_tab ? 'noopener ' : '') . ($child->isExternalUrl() ? 'nofollow' : '')) }}"
                               @endif
                               class="flex-1 py-1">
                                @if($child->icon)<i class="{{ $child->icon }} mr-1"></i>@endif{{ $child->label }}
                            </a>
                            @else
                            <span class="flex-1 py-1 text-base-content/70">{{ $child->label }}</span>
                            @endif

                            @if($childHasFlyout)
                            <button type="button" class="btn btn-ghost btn-xs btn-square shrink-0"
                                    aria-haspopup="true" :aria-expanded="(mobileFlySub === '{{ $flyKey }}').toString()"
                                    @click="mobileFlySub = mobileFlySub === '{{ $flyKey }}' ? null : '{{ $flyKey }}'">
                                <svg class="h-4 w-4 transition-transform" :class="mobileFlySub === '{{ $flyKey }}' ? 'rotate-180' : ''" viewBox="0 0 24 24" fill="none" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7" /></svg>
                            </button>
                            @endif
                        </div>

                        @if($childHasFlyout)
                        <ul x-show="mobileFlySub === '{{ $flyKey }}'" x-transition x-cloak class="pl-4">
                            @foreach($child->children as $grandchild)
                            <li><a href="{{ $grandchild->resolveUrl() ?? '#' }}"
                                   @if($grandchild->open_in_new_tab) target="_blank" @endif
                                   @if($grandchild->open_in_new_tab || $grandchild->isExternalUrl())
                                   rel="{{ trim(($grandchild->open_in_new_tab ? 'noopener ' : '') . ($grandchild->isExternalUrl() ? 'nofollow' : '')) }}"
                                   @endif
                               >{{ $grandchild->label }}</a></li>
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
</div>
