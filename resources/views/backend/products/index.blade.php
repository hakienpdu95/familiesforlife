@extends('layouts.backend')
@section('title', 'Sản phẩm')

@section('breadcrumb')
<nav class="breadcrumb-nav">
    <a href="{{ route('backend.dashboard') }}">Trang chủ</a>
    <span class="sep">›</span>
    <span class="current">Sản phẩm</span>
</nav>
@endsection

@section('content')
{{-- Page header --}}
<div class="flex flex-wrap items-center justify-between gap-3 mb-6">
    <div>
        <h1 class="text-2xl font-bold text-base-content">Sản phẩm</h1>
        <p class="text-sm text-base-content/50 mt-0.5">Quản lý toàn bộ danh sách sản phẩm</p>
    </div>
    <div class="flex gap-2">
        <a href="{{ route('backend.products.create') }}" class="btn btn-primary btn-sm gap-2">
            <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/></svg>
            Thêm sản phẩm
        </a>
    </div>
</div>

{{-- Filters --}}
<div class="card bg-base-100 shadow-sm border border-base-200 mb-4">
    <div class="card-body p-4">
        <div class="flex flex-wrap gap-3 items-end">
            <div class="form-control flex-1 min-w-[160px]">
                <label class="label py-0 pb-1"><span class="label-text text-xs font-medium">Tìm kiếm</span></label>
                <label class="input input-bordered input-sm flex items-center gap-2">
                    <svg class="w-4 h-4 opacity-50" fill="none" viewBox="0 0 24 24" stroke="currentColor"><circle cx="11" cy="11" r="8"/><path d="m21 21-4.3-4.3"/></svg>
                    <input type="text" placeholder="Tên, SKU..." class="grow" id="searchInput"/>
                </label>
            </div>
            <div class="form-control min-w-[130px]">
                <label class="label py-0 pb-1"><span class="label-text text-xs font-medium">Danh mục</span></label>
                <select class="select select-bordered select-sm">
                    <option value="">Tất cả</option>
                    <option>Điện thoại</option>
                    <option>Laptop</option>
                    <option>Phụ kiện</option>
                </select>
            </div>
            <div class="form-control min-w-[130px]">
                <label class="label py-0 pb-1"><span class="label-text text-xs font-medium">Trạng thái</span></label>
                <select class="select select-bordered select-sm">
                    <option value="">Tất cả</option>
                    <option>Đang bán</option>
                    <option>Hết hàng</option>
                    <option>Ngừng bán</option>
                </select>
            </div>
            <button class="btn btn-ghost btn-sm">Đặt lại</button>
        </div>
    </div>
</div>

{{-- Table --}}
<div class="card bg-base-100 shadow-sm border border-base-200">
    <div class="card-body p-0">
        <div class="overflow-x-auto">
            <table class="table table-sm" id="productsTable">
                <thead class="bg-base-200/50">
                    <tr>
                        <th class="w-10"><input type="checkbox" class="checkbox checkbox-sm" id="checkAll"/></th>
                        <th>Sản phẩm</th>
                        <th>SKU</th>
                        <th>Danh mục</th>
                        <th class="text-right">Giá bán</th>
                        <th class="text-center">Tồn kho</th>
                        <th class="text-center">Trạng thái</th>
                        <th class="text-center w-24">Thao tác</th>
                    </tr>
                </thead>
                <tbody>
                @php
                $products = [
                    ['id'=>1,'name'=>'iPhone 15 Pro 256GB','sku'=>'IPH15P-256','cat'=>'Điện thoại','price'=>'28,990,000','stock'=>15,'status'=>'active','img'=>'https://api.dicebear.com/9.x/shapes/svg?seed=iphone&backgroundColor=e0e7ff'],
                    ['id'=>2,'name'=>'MacBook Air M3 13"','sku'=>'MBA-M3-13','cat'=>'Laptop','price'=>'32,490,000','stock'=>8,'status'=>'active','img'=>'https://api.dicebear.com/9.x/shapes/svg?seed=macbook&backgroundColor=d1fae5'],
                    ['id'=>3,'name'=>'AirPods Pro 2nd Gen','sku'=>'APP-2GEN','cat'=>'Âm thanh','price'=>'6,290,000','stock'=>2,'status'=>'low','img'=>'https://api.dicebear.com/9.x/shapes/svg?seed=airpods&backgroundColor=fef3c7'],
                    ['id'=>4,'name'=>'Samsung Galaxy S24 Ultra','sku'=>'SGS24U','cat'=>'Điện thoại','price'=>'31,990,000','stock'=>0,'status'=>'out','img'=>'https://api.dicebear.com/9.x/shapes/svg?seed=samsung&backgroundColor=fce7f3'],
                    ['id'=>5,'name'=>'iPad Pro M4 11"','sku'=>'IPAD-M4-11','cat'=>'Máy tính bảng','price'=>'26,990,000','stock'=>12,'status'=>'active','img'=>'https://api.dicebear.com/9.x/shapes/svg?seed=ipad&backgroundColor=e0e7ff'],
                ];
                @endphp
                @foreach($products as $p)
                <tr class="hover">
                    <td><input type="checkbox" class="checkbox checkbox-sm row-check" value="{{ $p['id'] }}"/></td>
                    <td>
                        <div class="flex items-center gap-3">
                            <div class="avatar"><div class="w-9 h-9 rounded-lg overflow-hidden bg-base-200"><img src="{{ $p['img'] }}" alt="{{ $p['name'] }}"/></div></div>
                            <div>
                                <a href="{{ route('backend.products.edit', $p['id']) }}" class="font-semibold text-sm text-base-content hover:text-primary transition-colors line-clamp-1">{{ $p['name'] }}</a>
                            </div>
                        </div>
                    </td>
                    <td><span class="font-mono text-xs text-base-content/60">{{ $p['sku'] }}</span></td>
                    <td><span class="badge badge-ghost badge-sm">{{ $p['cat'] }}</span></td>
                    <td class="text-right font-semibold text-sm">₫ {{ $p['price'] }}</td>
                    <td class="text-center">
                        @if($p['status'] === 'out')
                            <span class="badge badge-error badge-sm">Hết hàng</span>
                        @elseif($p['status'] === 'low')
                            <span class="badge badge-warning badge-sm">{{ $p['stock'] }} còn</span>
                        @else
                            <span class="text-sm font-medium">{{ $p['stock'] }}</span>
                        @endif
                    </td>
                    <td class="text-center">
                        @if($p['status'] === 'active' || $p['status'] === 'low')
                            <span class="badge badge-success badge-sm gap-1"><span class="w-1.5 h-1.5 rounded-full bg-current"></span>Đang bán</span>
                        @else
                            <span class="badge badge-ghost badge-sm">Hết hàng</span>
                        @endif
                    </td>
                    <td>
                        <div class="flex items-center justify-center gap-1">
                            <a href="{{ route('backend.products.show', $p['id']) }}" class="btn btn-ghost btn-xs btn-square" title="Xem">
                                <svg class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"/></svg>
                            </a>
                            <a href="{{ route('backend.products.edit', $p['id']) }}" class="btn btn-ghost btn-xs btn-square" title="Sửa">
                                <svg class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"/></svg>
                            </a>
                            <button class="btn btn-ghost btn-xs btn-square text-error" title="Xoá"
                                    onclick="confirmDelete('{{ route('backend.products.destroy', $p['id']) }}','{{ $p['name'] }}')">
                                <svg class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/></svg>
                            </button>
                        </div>
                    </td>
                </tr>
                @endforeach
                </tbody>
            </table>
        </div>

        {{-- Table footer --}}
        <div class="flex flex-wrap items-center justify-between gap-3 px-4 py-3 border-t border-base-200">
            <div class="flex items-center gap-3">
                <span class="text-sm text-base-content/50">Hiển thị 1–5 / 128 sản phẩm</span>
                <div id="bulkActions" class="hidden flex gap-2">
                    <button class="btn btn-error btn-xs gap-1"><svg class="w-3 h-3" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/></svg>Xoá đã chọn</button>
                </div>
            </div>
            <div class="join">
                <button class="join-item btn btn-xs">«</button>
                <button class="join-item btn btn-xs btn-active">1</button>
                <button class="join-item btn btn-xs">2</button>
                <button class="join-item btn btn-xs">3</button>
                <button class="join-item btn btn-xs">»</button>
            </div>
        </div>
    </div>
</div>

{{-- Delete modal --}}
<dialog id="deleteModal" class="modal">
    <div class="modal-box max-w-sm">
        <h3 class="font-bold text-lg text-error">Xác nhận xoá</h3>
        <p class="py-3 text-sm text-base-content/70">Bạn có chắc muốn xoá sản phẩm <strong id="deleteItemName" class="text-base-content"></strong>?</p>
        <p class="text-xs text-error/70">Hành động này không thể hoàn tác.</p>
        <div class="modal-action mt-4">
            <form method="POST" id="deleteForm">
                @csrf @method('DELETE')
                <button type="submit" class="btn btn-error btn-sm">Xoá</button>
            </form>
            <button class="btn btn-ghost btn-sm" onclick="deleteModal.close()">Huỷ</button>
        </div>
    </div>
    <form method="dialog" class="modal-backdrop"><button>close</button></form>
</dialog>
@endsection

@push('scripts')
<script>
function confirmDelete(url, name) {
    document.getElementById('deleteForm').action = url;
    document.getElementById('deleteItemName').textContent = name;
    deleteModal.showModal();
}
// Bulk select
const checkAll = document.getElementById('checkAll');
const bulkActions = document.getElementById('bulkActions');
checkAll?.addEventListener('change', () => {
    document.querySelectorAll('.row-check').forEach(cb => cb.checked = checkAll.checked);
    bulkActions.classList.toggle('hidden', !checkAll.checked);
});
document.querySelectorAll('.row-check').forEach(cb => {
    cb.addEventListener('change', () => {
        const checked = document.querySelectorAll('.row-check:checked').length;
        bulkActions.classList.toggle('hidden', checked === 0);
        checkAll.indeterminate = checked > 0 && checked < document.querySelectorAll('.row-check').length;
    });
});
</script>
@endpush
