@extends('layouts.backend')
@section('title','Khách hàng')
@section('breadcrumb')
<nav class="breadcrumb-nav"><a href="{{ route('backend.dashboard') }}">Trang chủ</a><span class="sep">›</span><span class="current">Khách hàng</span></nav>
@endsection
@section('content')
<div class="flex flex-wrap items-center justify-between gap-3 mb-6">
    <div><h1 class="text-2xl font-bold text-base-content">Khách hàng</h1><p class="text-sm text-base-content/50 mt-0.5">Quản lý tất cả khách hàng</p></div>
    <div class="flex gap-2">
        <a href="{{ route('backend.customers.create') }}" class="btn btn-primary btn-sm gap-2"><svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/></svg>Thêm khách hàng</a>
    </div>
</div>

{{-- Stats --}}
<div class="grid grid-cols-2 md:grid-cols-4 gap-4 mb-5">
    @foreach([['Tổng KH','4,921','text-primary'],['KH mới tháng này','142','text-success'],['KH hoạt động','3,284','text-info'],['Đã chặn','12','text-error']] as [$label,$val,$color])
    <div class="card bg-base-100 shadow-sm border border-base-200"><div class="card-body p-4"><p class="text-xs text-base-content/50 uppercase tracking-wide font-semibold mb-1">{{ $label }}</p><p class="text-2xl font-bold {{ $color }}">{{ $val }}</p></div></div>
    @endforeach
</div>

<div class="card bg-base-100 shadow-sm border border-base-200">
    <div class="card-body p-4 border-b border-base-200">
        <div class="flex flex-wrap gap-3">
            <label class="input input-bordered input-sm flex items-center gap-2 flex-1 min-w-[200px] max-w-xs"><svg class="w-4 h-4 opacity-40" fill="none" viewBox="0 0 24 24" stroke="currentColor"><circle cx="11" cy="11" r="8"/><path d="m21 21-4.3-4.3"/></svg><input type="search" placeholder="Tìm khách hàng..."></label>
            <select class="select select-bordered select-sm"><option>Tất cả trạng thái</option><option>Hoạt động</option><option>Không hoạt động</option><option>Đã chặn</option></select>
        </div>
    </div>
    <div class="overflow-x-auto">
        <table class="table table-sm">
            <thead><tr class="text-xs text-base-content/50 uppercase tracking-wide bg-base-50"><th><input type="checkbox" class="checkbox checkbox-sm"/></th><th>Khách hàng</th><th class="hidden md:table-cell">Điện thoại</th><th class="hidden lg:table-cell">Đơn hàng</th><th class="hidden sm:table-cell">Tổng chi tiêu</th><th>Ngày ĐK</th><th>Trạng thái</th><th></th></tr></thead>
            <tbody>
                @php
                $customers=[
                    ['name'=>'Nguyễn Văn A','email'=>'nva@gmail.com','av'=>'N','phone'=>'0901 234 567','orders'=>24,'spent'=>'72,450,000','joined'=>'01/01/2024','status'=>'active'],
                    ['name'=>'Trần Thị B',  'email'=>'ttb@gmail.com','av'=>'T','phone'=>'0912 345 678','orders'=>18,'spent'=>'48,900,000','joined'=>'15/02/2024','status'=>'active'],
                    ['name'=>'Lê Văn C',    'email'=>'lvc@gmail.com','av'=>'L','phone'=>'0923 456 789','orders'=>5, 'spent'=>'12,300,000','joined'=>'10/03/2024','status'=>'inactive'],
                    ['name'=>'Phạm Thị D',  'email'=>'ptd@gmail.com','av'=>'P','phone'=>'0934 567 890','orders'=>31,'spent'=>'95,200,000','joined'=>'05/12/2023','status'=>'active'],
                    ['name'=>'Hoàng Văn E', 'email'=>'hve@gmail.com','av'=>'H','phone'=>'0945 678 901','orders'=>2, 'spent'=>'5,800,000', 'joined'=>'22/04/2025','status'=>'banned'],
                ];
                $sMap=['active'=>['Hoạt động','success'],'inactive'=>['Không HĐ','ghost'],'banned'=>['Đã chặn','error']];
                @endphp
                @foreach($customers as $c)
                @php [$sl,$sc]=$sMap[$c['status']]; @endphp
                <tr class="border-b border-base-100 last:border-0 hover">
                    <td><input type="checkbox" class="checkbox checkbox-sm"/></td>
                    <td><div class="flex items-center gap-3"><div class="avatar placeholder"><div class="w-9 h-9 rounded-full bg-neutral text-neutral-content text-xs font-bold"><span>{{ $c['av'] }}</span></div></div><div><p class="font-medium text-sm">{{ $c['name'] }}</p><p class="text-xs text-base-content/50">{{ $c['email'] }}</p></div></div></td>
                    <td class="hidden md:table-cell text-sm text-base-content/70">{{ $c['phone'] }}</td>
                    <td class="hidden lg:table-cell text-sm">{{ $c['orders'] }}</td>
                    <td class="hidden sm:table-cell"><span class="text-sm font-semibold">₫ {{ $c['spent'] }}</span></td>
                    <td class="text-xs text-base-content/60">{{ $c['joined'] }}</td>
                    <td><span class="badge badge-{{ $sc }} badge-sm">{{ $sl }}</span></td>
                    <td><div class="flex justify-end gap-1"><a href="#" class="btn btn-ghost btn-xs btn-circle"><svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"/></svg></a><a href="#" class="btn btn-ghost btn-xs btn-circle"><svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"/></svg></a></div></td>
                </tr>
                @endforeach
            </tbody>
        </table>
    </div>
    <div class="flex flex-wrap items-center justify-between gap-3 px-5 py-3 border-t border-base-200"><span class="text-xs text-base-content/50">Hiển thị 5 / 4,921 khách hàng</span><div class="join"><button class="join-item btn btn-xs">«</button><button class="join-item btn btn-xs btn-active">1</button><button class="join-item btn btn-xs">2</button><button class="join-item btn btn-xs">»</button></div></div>
</div>
@endsection
