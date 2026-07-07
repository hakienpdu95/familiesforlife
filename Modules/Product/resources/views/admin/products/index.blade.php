@extends('layouts.backend')
@section('title', 'Sản phẩm & Dịch vụ')

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

    @if($errors->any())
    <div class="alert alert-error mb-4 text-sm">
        <ul class="list-disc pl-4 space-y-0.5">
            @foreach($errors->all() as $error)<li>{{ $error }}</li>@endforeach
        </ul>
    </div>
    @endif

    <div class="flex flex-wrap items-center justify-between gap-3 mb-5">
        <div>
            <h1 class="text-2xl font-bold text-base-content">Sản phẩm & Dịch vụ</h1>
            <p class="text-sm text-base-content/50 mt-0.5">Catalog dùng để gọi ra trong Product CTA Box của bài viết</p>
        </div>
        <div class="flex items-center gap-2">
            @can(\App\Enums\PermissionEnum::PRODUCT_CATEGORY_MANAGE->value)
            <a href="{{ route('backend.products.categories.index') }}" class="btn btn-ghost btn-sm">Danh mục</a>
            @endcan
            @can('create', \Modules\Product\Models\Product::class)
            <a href="{{ route('backend.products.create') }}" class="btn btn-primary btn-sm gap-1.5">
                <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/>
                </svg>
                Thêm sản phẩm
            </a>
            @endcan
        </div>
    </div>

    <form method="GET" class="flex flex-wrap gap-2 mb-5">
        <input type="text" name="q" value="{{ request('q') }}" placeholder="Tìm tên/SKU..."
               class="input input-bordered input-sm w-56">
        <select name="category_id" class="select select-bordered select-sm">
            <option value="">— Tất cả danh mục —</option>
            @foreach($categories as $c)
            <option value="{{ $c->id }}" @selected((string) request('category_id') === (string) $c->id)>{{ $c->name }}</option>
            @endforeach
        </select>
        <select name="status" class="select select-bordered select-sm">
            <option value="">— Tất cả trạng thái —</option>
            @foreach(\Modules\Product\Enums\ProductStatus::cases() as $s)
            <option value="{{ $s->value }}" @selected(request('status') === $s->value)>{{ $s->label() }}</option>
            @endforeach
        </select>
        <select name="type" class="select select-bordered select-sm">
            <option value="">— Tất cả loại —</option>
            @foreach(\Modules\Product\Enums\ProductType::cases() as $t)
            <option value="{{ $t->value }}" @selected(request('type') === $t->value)>{{ $t->label() }}</option>
            @endforeach
        </select>
        <button class="btn btn-sm btn-neutral">Lọc</button>
        @if(request('q') || request('category_id') || request('status') || request('type'))
        <a href="{{ route('backend.products.index') }}" class="btn btn-sm btn-ghost">Xoá lọc</a>
        @endif
    </form>

    <div class="card bg-base-100 shadow-sm border border-base-200 overflow-hidden">
        <div class="overflow-x-auto">
            <table class="table table-sm">
                <thead class="bg-base-200/60 text-xs uppercase tracking-wide">
                    <tr>
                        <th>Sản phẩm</th>
                        <th>Danh mục</th>
                        <th>Loại</th>
                        <th class="text-right">Giá</th>
                        <th class="text-center">Trạng thái</th>
                        <th class="text-center">Bài viết dùng</th>
                        <th class="w-24"></th>
                    </tr>
                </thead>
                <tbody>
                @forelse ($products as $p)
                <tr class="hover">
                    <td>
                        <span class="font-medium text-sm">{{ $p->name }}</span>
                        @if($p->sku)<div class="text-xs text-base-content/40 font-mono">{{ $p->sku }}</div>@endif
                    </td>
                    <td class="text-sm text-base-content/60">{{ $p->category?->name ?? '—' }}</td>
                    <td class="text-sm text-base-content/60">{{ $p->type->label() }}</td>
                    <td class="text-right text-sm">{{ $p->display_price ?? '—' }}</td>
                    <td class="text-center">
                        <span class="badge badge-sm {{ $p->status->badgeClass() }}">{{ $p->status->label() }}</span>
                    </td>
                    <td class="text-center text-sm">
                        @if($p->used_in_articles_count > 0)
                            <span class="badge badge-sm badge-info">{{ $p->used_in_articles_count }}</span>
                        @else
                            <span class="text-base-content/30">0</span>
                        @endif
                    </td>
                    <td>
                        <div class="flex gap-1">
                            @can('update', $p)
                            <a href="{{ route('backend.products.edit', $p) }}" class="btn btn-ghost btn-xs btn-square" title="Sửa">
                                <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"/>
                                </svg>
                            </a>
                            @endcan
                            @can('delete', $p)
                            <form method="POST" action="{{ route('backend.products.destroy', $p) }}"
                                  onsubmit="return confirm('Xoá sản phẩm &quot;{{ $p->name }}&quot;?')">
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
        @if($products->hasPages())
        <div class="p-3 border-t border-base-200">{{ $products->links() }}</div>
        @endif
    </div>
</div>
@endsection
