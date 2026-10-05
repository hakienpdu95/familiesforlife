<form id="brand-filter" method="GET" action="{{ url()->current() }}#products"
      class="mb-5 flex flex-col gap-3 rounded-sm bg-[#f8f9fa] p-3 sm:flex-row sm:items-center sm:justify-between">
    <input type="hidden" name="sort" value="{{ $filters['sort'] }}">

    <div class="flex flex-wrap items-center gap-2 text-sm">
        <span class="text-[#555]">Sắp xếp theo</span>
        @foreach($sorts as $key => $label)
        <a href="{{ request()->fullUrlWithQuery(['sort' => $key, 'page' => null]) }}#products"
           class="btn btn-sm rounded-sm border-0 font-normal {{ $filters['sort'] === $key ? 'bg-[#117a3a] text-white hover:bg-[#0d6230]' : 'bg-white text-[#333] hover:text-[#117a3a]' }}">
            {{ $label }}
        </a>
        @endforeach
    </div>

    <label class="input input-sm flex w-full items-center gap-2 rounded-sm bg-white sm:w-64">
        <svg class="h-4 w-4 opacity-50" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-4.35-4.35M17 11a6 6 0 11-12 0 6 6 0 0112 0z"/></svg>
        <input type="search" name="q" value="{{ $filters['q'] }}" placeholder="Tìm sản phẩm của thương hiệu..." class="grow">
    </label>
</form>

@if($filters['q'] !== '' || $filters['stars'])
<div class="mb-4 flex flex-wrap items-center gap-2 text-sm text-[#555]">
    <span>Tìm thấy <strong>{{ $products->total() }}</strong> sản phẩm</span>
    <a href="{{ url()->current() }}#products" class="link link-hover text-[#117a3a]">Xoá bộ lọc</a>
</div>
@endif

<div class="grid grid-cols-2 md:grid-cols-3 lg:grid-cols-4 gap-4">
    @forelse($products as $product)
        @include('ocopsubject::public.partials._product-card', ['product' => $product])
    @empty
    <p class="col-span-full py-10 text-center text-base-content/40">Chưa có sản phẩm nào.</p>
    @endforelse
</div>

@if($products->hasPages())
<div class="mt-8">{{ $products->onEachSide(1)->links() }}</div>
@endif
