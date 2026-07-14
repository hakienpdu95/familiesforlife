{{-- Navbar danh mục — dữ liệu thật từ PostCategory::navTree() (root + children active),
     KHÔNG phải mảng Alpine x-for hard-code như bản mẫu tĩnh: render server-side (Blade
     @foreach) để crawler/SEO thấy ngay trong HTML, Alpine chỉ điều khiển việc mở/đóng
     dropdown (subOpen, xem resources/js/frontend.js). --}}
@if(($categories ?? collect())->isNotEmpty())
<nav class="bg-primary sticky top-0 z-40 shadow-md hidden lg:block">
    <div class="max-w-6xl mx-auto px-4">
        <ul class="menu menu-horizontal gap-1 text-xs font-bold uppercase tracking-wide px-0 justify-center w-full">
            @foreach($categories as $cat)
            <li class="relative" @if($cat->children->isNotEmpty()) @mouseleave="subOpen === {{ $loop->index }} ? subOpen = null : null" @endif>
                <a href="{{ $cat->children->isNotEmpty() ? '#' : route('post.public.category', ['category' => $cat->slug]) }}"
                   class="rounded-none py-4 text-primary-content"
                   @if($cat->children->isNotEmpty())
                   @click.prevent="subOpen = subOpen === {{ $loop->index }} ? null : {{ $loop->index }}"
                   :class="subOpen === {{ $loop->index }} ? 'bg-black/10 text-warning' : ''"
                   @endif
                >{{ $cat->name }}</a>

                @if($cat->children->isNotEmpty())
                {{-- <ul>, không phải <div>: selector component .menu của daisyui nhắm
                     `li > :not(ul,details,.menu-title,.btn)` để tự set display:grid cho item
                     ngang hàng — dùng <div> ở đây bị dính chọn nhầm, vỡ layout item con. --}}
                <ul x-show="subOpen === {{ $loop->index }}" x-transition x-cloak @click.outside="subOpen = null"
                    class="menu menu-sm absolute left-0 top-full w-56 flex-nowrap bg-base-100 text-base-content rounded-b-lg shadow-lg z-50 normal-case">
                    <li><a href="{{ route('post.public.category', ['category' => $cat->slug]) }}"
                           class="font-bold hover:text-primary">Tất cả {{ $cat->name }}</a></li>
                    <li><div class="divider my-0"></div></li>
                    @foreach($cat->children as $child)
                    <li><a href="{{ route('post.public.category', ['category' => $child->slug]) }}"
                           class="hover:text-primary">{{ $child->name }}</a></li>
                    @endforeach
                </ul>
                @endif
            </li>
            @endforeach
        </ul>
    </div>
</nav>
@endif
