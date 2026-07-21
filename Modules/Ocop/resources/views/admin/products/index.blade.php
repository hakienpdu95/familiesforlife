@extends('layouts.backend')
@section('title', 'Sản phẩm OCOP')

@section('content')
<div>

    @foreach(['success','error'] as $type)
        @if(session($type))
        <div x-data="{ show: true }" x-show="show" x-init="setTimeout(() => show = false, 4000)"
             x-transition.opacity.duration.500ms
             class="alert alert-{{ $type }} mb-4 text-sm">
            <span>{{ session($type) }}</span>
            <button @click="show = false" class="btn btn-ghost btn-xs ml-auto">✕</button>
        </div>
        @endif
    @endforeach

    <div class="flex flex-wrap items-center justify-between gap-3 mb-5">
        <div>
            <h1 class="text-2xl font-bold text-base-content">Sản phẩm OCOP</h1>
            <p class="text-sm text-base-content/50 mt-0.5">Sản phẩm đặc trưng OCOP theo tỉnh</p>
        </div>
        <div class="flex items-center gap-2">
            <a href="{{ route('backend.ocop.categories.index') }}" class="btn btn-ghost btn-sm">Danh mục OCOP</a>
            @can('create', \Modules\Ocop\Models\OcopProduct::class)
            <a href="{{ route('backend.ocop.products.create') }}" class="btn btn-primary btn-sm gap-1.5">
                <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/>
                </svg>
                Thêm sản phẩm
            </a>
            @endcan
        </div>
    </div>

    <form method="GET" class="flex flex-wrap gap-2 mb-5">
        <input type="text" name="q" value="{{ request('q') }}" placeholder="Tìm tên sản phẩm..."
               class="input input-bordered input-sm w-56">
        <select name="category_id" class="select select-bordered select-sm">
            <option value="">— Tất cả danh mục —</option>
            @foreach($categories as $c)
            <option value="{{ $c->id }}" {{ (string) request('category_id') === (string) $c->id ? 'selected' : '' }}>{{ $c->name }}</option>
            @endforeach
        </select>
        <select name="status" class="select select-bordered select-sm">
            <option value="">— Tất cả trạng thái —</option>
            <option value="draft" {{ request('status') === 'draft' ? 'selected' : '' }}>Nháp</option>
            <option value="published" {{ request('status') === 'published' ? 'selected' : '' }}>Đã xuất bản</option>
        </select>
        <button class="btn btn-sm btn-neutral">Lọc</button>
        @if(request('q') || request('category_id') || request('status'))
        <a href="{{ route('backend.ocop.products.index') }}" class="btn btn-sm btn-ghost">Xoá lọc</a>
        @endif
    </form>

    <div class="card bg-base-100 shadow-sm border border-base-200 overflow-hidden">
        <div class="overflow-x-auto">
            <table class="table table-sm">
                <thead class="bg-base-200/60 text-xs uppercase tracking-wide">
                    <tr>
                        <th>Ảnh</th>
                        <th>Sản phẩm</th>
                        <th>Danh mục</th>
                        <th class="text-center">Hạng sao</th>
                        <th>Tỉnh/thành</th>
                        <th class="text-center">Trạng thái</th>
                        <th class="w-24"></th>
                    </tr>
                </thead>
                <tbody>
                @forelse ($products as $product)
                <tr class="hover">
                    <td>
                        @if($product->getFirstMediaUrl('cover'))
                        <img src="{{ $product->getFirstMediaUrl('cover', 'thumb') }}" alt=""
                             class="h-10 w-10 rounded border border-base-300 object-cover">
                        @else
                        <div class="h-10 w-10 rounded border border-base-300 bg-base-200"></div>
                        @endif
                    </td>
                    <td>
                        <span class="font-medium text-sm">{{ $product->name }}</span>
                        @if($product->is_featured)
                        <span class="badge badge-warning badge-xs ml-1">Nổi bật</span>
                        @endif
                    </td>
                    <td class="text-sm text-base-content/60">{{ $product->category?->name ?? '—' }}</td>
                    <td class="text-center text-sm">{{ $product->star_rating }} ★</td>
                    <td class="text-sm text-base-content/60">{{ $product->province_name ?? '—' }}</td>
                    <td class="text-center">
                        <span class="badge badge-sm {{ $product->status->value === 'published' ? 'badge-success' : 'badge-ghost' }}">
                            {{ $product->status->label() }}
                        </span>
                    </td>
                    <td>
                        <div class="flex gap-1">
                            @can('update', $product)
                            <a href="{{ route('backend.ocop.products.edit', $product) }}" class="btn btn-ghost btn-xs btn-square" title="Sửa">
                                <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"/>
                                </svg>
                            </a>
                            @endcan
                            @can('delete', $product)
                            <form method="POST" action="{{ route('backend.ocop.products.destroy', $product) }}"
                                  onsubmit="return confirm('Xoá sản phẩm &quot;{{ $product->name }}&quot;?')">
                                @csrf @method('DELETE')
                                <button class="btn btn-ghost btn-xs btn-square text-error" title="Xoá">
                                    <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/>
                                    </svg>
                                </button>
                            </form>
                            @endcan
                        </div>
                    </td>
                </tr>
                @empty
                <tr>
                    <td colspan="7" class="text-center py-8 text-base-content/40">Chưa có sản phẩm nào.</td>
                </tr>
                @endforelse
                </tbody>
            </table>
        </div>
    </div>

    @if($products->hasPages())
    <div class="mt-4">{{ $products->onEachSide(1)->links() }}</div>
    @endif
</div>
@endsection
