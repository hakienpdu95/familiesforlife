@extends('layouts.backend')
@section('title', 'Danh mục sản phẩm')

@section('content')
<div x-data="{ confirmDelete: null }">

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
            <h1 class="text-2xl font-bold text-base-content">Danh mục sản phẩm</h1>
            <p class="text-sm text-base-content/50 mt-0.5">Cây danh mục dùng cho catalog sản phẩm/dịch vụ</p>
        </div>
        <div class="flex items-center gap-2">
            <a href="{{ route('backend.products.index') }}" class="btn btn-ghost btn-sm">← Sản phẩm</a>
            @can(\App\Enums\PermissionEnum::PRODUCT_CATEGORY_MANAGE->value)
            <a href="{{ route('backend.products.categories.create') }}" class="btn btn-primary btn-sm gap-1.5">
                <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/>
                </svg>
                Thêm danh mục
            </a>
            @endcan
        </div>
    </div>

    <form method="GET" class="flex flex-wrap gap-2 mb-5">
        <input type="text" name="q" value="{{ request('q') }}" placeholder="Tìm tên danh mục..."
               class="input input-bordered input-sm w-56">
        <button class="btn btn-sm btn-neutral">Lọc</button>
        @if(request('q'))
        <a href="{{ route('backend.products.categories.index') }}" class="btn btn-sm btn-ghost">Xoá lọc</a>
        @endif
    </form>

    <div class="card bg-base-100 shadow-sm border border-base-200 overflow-hidden">
        <div class="overflow-x-auto">
            <table class="table table-sm">
                <thead class="bg-base-200/60 text-xs uppercase tracking-wide">
                    <tr>
                        <th>Tên danh mục</th>
                        <th>Danh mục cha</th>
                        <th class="text-center">Số sản phẩm</th>
                        <th class="text-center">Trạng thái</th>
                        <th class="w-24"></th>
                    </tr>
                </thead>
                <tbody>
                @forelse ($categories as $cat)
                <tr class="hover">
                    <td>
                        <span class="font-medium text-sm">{{ $cat->name }}</span>
                        <div class="text-xs text-base-content/40 font-mono">{{ $cat->slug }}</div>
                    </td>
                    <td class="text-sm text-base-content/60">{{ $cat->parent?->name ?? '—' }}</td>
                    <td class="text-center text-sm">{{ $cat->products_count }}</td>
                    <td class="text-center">
                        <span class="badge badge-sm {{ $cat->is_active ? 'badge-success' : 'badge-ghost' }}">
                            {{ $cat->is_active ? 'Hiện' : 'Ẩn' }}
                        </span>
                    </td>
                    <td>
                        <div class="flex gap-1">
                            @can('update', $cat)
                            <a href="{{ route('backend.products.categories.edit', $cat) }}" class="btn btn-ghost btn-xs btn-square" title="Sửa">
                                <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"/>
                                </svg>
                            </a>
                            @endcan
                            @can('delete', $cat)
                            <form method="POST" action="{{ route('backend.products.categories.destroy', $cat) }}"
                                  onsubmit="return confirm('Xoá danh mục &quot;{{ $cat->name }}&quot;?')">
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
                    <td colspan="5" class="text-center py-8 text-base-content/40">Chưa có danh mục nào.</td>
                </tr>
                @endforelse
                </tbody>
            </table>
        </div>
    </div>
</div>
@endsection
