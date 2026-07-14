{{-- Thanh tiện ích trên cùng: chuyển ngôn ngữ (config('post.locales'), y hệt dữ liệu dùng ở
     home/category/article hiện có) + ô tìm kiếm (nộp GET ?q= về CHÍNH trang đang xem qua
     url()->current() — hoạt động cả ở trang chủ lẫn trang danh mục vì cả hai controller đều
     đọc $request->string('q') và truyền cho ListPublishedArticlesHandler). --}}
<div class="bg-base-100 border-b border-base-300">
    <div class="max-w-6xl mx-auto px-4 py-2 flex items-center justify-between">
        <div class="flex items-center gap-1 text-xs font-bold uppercase tracking-wide">
            @foreach(config('post.locales') as $code => $label)
            <a href="{{ route('post.public.home', ['locale' => $code]) }}"
               class="px-2 py-1 rounded-full {{ ($locale ?? null) === $code ? 'bg-primary text-primary-content' : 'text-base-content/60 hover:bg-base-200' }}">{{ $label }}</a>
            @endforeach
        </div>
        <button type="button" class="btn btn-ghost btn-square btn-sm" @click="search = !search" aria-label="Tìm kiếm">
            <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-4.35-4.35M17 11a6 6 0 11-12 0 6 6 0 0112 0z" /></svg>
        </button>
    </div>
    <div x-show="search" x-transition x-cloak class="max-w-6xl mx-auto px-4 pb-3">
        <form method="GET" action="{{ url()->current() }}">
            <label class="input input-bordered flex items-center gap-2 w-full">
                <svg class="h-4 w-4 opacity-50" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-4.35-4.35M17 11a6 6 0 11-12 0 6 6 0 0112 0z" /></svg>
                <input type="search" name="q" value="{{ $search ?? '' }}" class="grow" placeholder="Tìm bài viết…">
            </label>
        </form>
    </div>
</div>
