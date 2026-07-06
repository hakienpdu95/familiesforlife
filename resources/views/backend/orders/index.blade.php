@extends('layouts.backend')
@section('title','Đơn hàng')
@section('breadcrumb')
<nav class="breadcrumb-nav">
    <a href="{{ route('backend.dashboard') }}">Trang chủ</a><span class="sep">›</span>
    <span class="current">Đơn hàng</span>
</nav>
@endsection
@section('content')
<div class="flex flex-wrap items-center justify-between gap-3 mb-6">
    <div><h1 class="text-2xl font-bold text-base-content">Đơn hàng</h1><p class="text-sm text-base-content/50 mt-0.5">Quản lý tất cả đơn hàng</p></div>
    <div class="flex gap-2">
        <button class="btn btn-ghost btn-sm gap-2"><svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4"/></svg>Xuất Excel</button>
    </div>
</div>

{{-- Status tabs --}}
<div class="tabs tabs-bordered mb-4">
    <a class="tab tab-active gap-2">Tất cả <span class="badge badge-sm">284</span></a>
    <a class="tab gap-2">Chờ xử lý <span class="badge badge-warning badge-sm">12</span></a>
    <a class="tab gap-2">Đang giao <span class="badge badge-info badge-sm">45</span></a>
    <a class="tab gap-2">Hoàn thành <span class="badge badge-success badge-sm">218</span></a>
    <a class="tab gap-2">Đã huỷ <span class="badge badge-error badge-sm">9</span></a>
</div>

<div class="card bg-base-100 shadow-sm border border-base-200">
    <div class="card-body p-4 border-b border-base-200">
        <div class="flex flex-wrap gap-3">
            <label class="input input-bordered input-sm flex items-center gap-2 flex-1 min-w-[200px] max-w-xs">
                <svg class="w-4 h-4 opacity-40" fill="none" viewBox="0 0 24 24" stroke="currentColor"><circle cx="11" cy="11" r="8"/><path d="m21 21-4.3-4.3"/></svg>
                <input type="search" placeholder="Tìm đơn hàng, khách hàng...">
            </label>
            <input type="text" id="date-range" class="input input-bordered input-sm w-52" placeholder="Khoảng ngày">
            <select class="select select-bordered select-sm"><option>Tất cả phương thức TT</option><option>COD</option><option>Chuyển khoản</option><option>Thẻ tín dụng</option></select>
        </div>
    </div>
    <div class="overflow-x-auto">
        <table class="table table-sm">
            <thead>
                <tr class="text-xs text-base-content/50 uppercase tracking-wide bg-base-50">
                    <th><input type="checkbox" class="checkbox checkbox-sm"/></th>
                    <th>Đơn hàng</th>
                    <th>Khách hàng</th>
                    <th class="hidden md:table-cell">Ngày đặt</th>
                    <th class="hidden sm:table-cell">Phương thức</th>
                    <th>Tổng tiền</th>
                    <th>Trạng thái</th>
                    <th></th>
                </tr>
            </thead>
            <tbody>
                @php
                $orders=[
                    ['id'=>'#1024','name'=>'Nguyễn Văn A','av'=>'N','date'=>'13/05/2025 10:25','method'=>'COD',      'total'=>'2,450,000','status'=>'completed','color'=>'success','label'=>'Hoàn thành'],
                    ['id'=>'#1023','name'=>'Trần Thị B',  'av'=>'T','date'=>'13/05/2025 09:12','method'=>'Chuyển khoản','total'=>'890,000',  'status'=>'shipping', 'color'=>'info',   'label'=>'Đang giao'],
                    ['id'=>'#1022','name'=>'Lê Văn C',    'av'=>'L','date'=>'12/05/2025 16:40','method'=>'COD',      'total'=>'5,120,000','status'=>'pending',  'color'=>'warning','label'=>'Chờ xử lý'],
                    ['id'=>'#1021','name'=>'Phạm Thị D',  'av'=>'P','date'=>'12/05/2025 14:05','method'=>'Thẻ TD',   'total'=>'1,680,000','status'=>'completed','color'=>'success','label'=>'Hoàn thành'],
                    ['id'=>'#1020','name'=>'Hoàng Văn E', 'av'=>'H','date'=>'11/05/2025 11:30','method'=>'COD',      'total'=>'3,240,000','status'=>'cancelled','color'=>'error',  'label'=>'Đã huỷ'],
                    ['id'=>'#1019','name'=>'Vũ Thị F',    'av'=>'V','date'=>'11/05/2025 09:00','method'=>'Chuyển khoản','total'=>'4,990,000','status'=>'shipping', 'color'=>'info',   'label'=>'Đang giao'],
                ];
                @endphp
                @foreach($orders as $o)
                <tr class="border-b border-base-100 last:border-0 hover">
                    <td><input type="checkbox" class="checkbox checkbox-sm"/></td>
                    <td><span class="font-mono text-sm font-semibold text-primary">{{ $o['id'] }}</span></td>
                    <td>
                        <div class="flex items-center gap-2">
                            <div class="avatar placeholder"><div class="w-7 h-7 rounded-full bg-neutral text-neutral-content text-xs font-bold"><span>{{ $o['av'] }}</span></div></div>
                            <span class="text-sm font-medium whitespace-nowrap">{{ $o['name'] }}</span>
                        </div>
                    </td>
                    <td class="hidden md:table-cell"><span class="text-xs text-base-content/60">{{ $o['date'] }}</span></td>
                    <td class="hidden sm:table-cell"><span class="badge badge-ghost badge-sm">{{ $o['method'] }}</span></td>
                    <td><span class="text-sm font-semibold whitespace-nowrap">₫ {{ $o['total'] }}</span></td>
                    <td><span class="badge badge-{{ $o['color'] }} badge-sm">{{ $o['label'] }}</span></td>
                    <td>
                        <div class="flex justify-end gap-1">
                            <a href="#" class="btn btn-ghost btn-xs btn-circle"><svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"/></svg></a>
                            <div class="dropdown dropdown-end">
                                <button tabindex="0" class="btn btn-ghost btn-xs btn-circle"><svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 12h.01M12 12h.01M19 12h.01M6 12a1 1 0 11-2 0 1 1 0 012 0zm7 0a1 1 0 11-2 0 1 1 0 012 0zm7 0a1 1 0 11-2 0 1 1 0 012 0z"/></svg></button>
                                <ul tabindex="0" class="dropdown-content z-50 menu menu-sm p-2 shadow bg-base-100 rounded-box w-40 border border-base-200">
                                    <li><a>Xác nhận</a></li>
                                    <li><a>In đơn</a></li>
                                    <li><a class="text-error">Huỷ đơn</a></li>
                                </ul>
                            </div>
                        </div>
                    </td>
                </tr>
                @endforeach
            </tbody>
        </table>
    </div>
    <div class="flex flex-wrap items-center justify-between gap-3 px-5 py-3 border-t border-base-200">
        <span class="text-xs text-base-content/50">Hiển thị 6 / 284 đơn hàng</span>
        <div class="join"><button class="join-item btn btn-xs">«</button><button class="join-item btn btn-xs btn-active">1</button><button class="join-item btn btn-xs">2</button><button class="join-item btn btn-xs">3</button><button class="join-item btn btn-xs">»</button></div>
    </div>
</div>
@endsection
@push('scripts')
<script>document.addEventListener('DOMContentLoaded',()=>{ if(window.initDateRangePicker) initDateRangePicker('#date-range'); })</script>
@endpush
