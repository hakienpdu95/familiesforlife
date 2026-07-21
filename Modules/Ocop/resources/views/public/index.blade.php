@extends('layouts.frontend')

@section('title', 'Sản phẩm OCOP')
@section('meta_description', 'Sản phẩm đặc trưng OCOP các địa phương — hạng sao, nhà sản xuất, thông tin liên hệ mua hàng.')

@section('content')
<div class="container py-10">

    <div class="text-xs breadcrumbs mb-4">
        <ul>
            <li><a href="{{ route('post.public.home') }}">Trang Chủ</a></li>
            <li>Sản phẩm OCOP</li>
        </ul>
    </div>

    <h1 class="text-2xl font-bold text-base-content mb-6">Sản phẩm OCOP</h1>

    <form method="GET" class="flex flex-wrap gap-2 mb-6">
        <input type="text" name="q" value="{{ request('q') }}" placeholder="Tìm tên sản phẩm..."
               class="input input-bordered input-sm w-56">
        <select name="category_id" class="select select-bordered select-sm">
            <option value="">— Tất cả danh mục —</option>
            @foreach($categories as $c)
            <option value="{{ $c->id }}" {{ (string) request('category_id') === (string) $c->id ? 'selected' : '' }}>{{ $c->name }}</option>
            @endforeach
        </select>
        @if(request('province'))
        <input type="hidden" name="province" value="{{ request('province') }}">
        @endif
        <button class="btn btn-sm btn-primary">Lọc</button>
        @if(request('q') || request('category_id'))
        <a href="{{ route('ocop.public.index', array_filter(['province' => request('province')])) }}" class="btn btn-sm btn-ghost">Xoá lọc</a>
        @endif
    </form>

    <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-6">
        @forelse($products as $product)
        <a href="{{ route('ocop.public.show', ['slug' => $product->slug]) }}" class="group flex flex-col gap-3">
            <div class="aspect-square rounded-xl overflow-hidden bg-base-200">
                @if($product->getFirstMediaUrl('cover'))
                <img src="{{ $product->getFirstMediaUrl('cover', 'medium') }}" alt="{{ $product->name }}" class="h-full w-full object-cover" loading="lazy">
                @else
                <div class="ph h-full w-full"></div>
                @endif
            </div>
            <div>
                @if($product->category)
                <span class="text-xs font-black uppercase tracking-wide text-primary">{{ $product->category->name }}</span>
                @endif
                <h3 class="font-bold leading-snug group-hover:text-primary mt-1">{{ $product->name }}</h3>
                <p class="mt-1 text-xs text-base-content/60">{{ str_repeat('★', $product->star_rating) }} · {{ $product->province_name ?? 'Chưa rõ địa phương' }}</p>
            </div>
        </a>
        @empty
        <p class="col-span-full text-center py-10 text-base-content/40">Chưa có sản phẩm nào.</p>
        @endforelse
    </div>

    @if($products->hasPages())
    <div class="mt-8">{{ $products->onEachSide(1)->links() }}</div>
    @endif

</div>
@endsection
