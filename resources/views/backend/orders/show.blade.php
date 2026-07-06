@extends('layouts.backend')
@section('title','Chi tiết đơn hàng')
@section('breadcrumb')
<nav class="breadcrumb-nav"><a href="{{ route('backend.dashboard') }}">Trang chủ</a><span class="sep">›</span><a href="{{ route('backend.orders.index') }}">Đơn hàng</a><span class="sep">›</span><span class="current">#1024</span></nav>
@endsection
@section('content')
<div class="flex flex-wrap items-center justify-between gap-3 mb-6">
    <div><h1 class="text-2xl font-bold text-base-content">Đơn hàng <span class="text-primary font-mono">#1024</span></h1></div>
    <div class="flex gap-2">
        <button class="btn btn-ghost btn-sm gap-1"><svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 17h2a2 2 0 002-2v-4a2 2 0 00-2-2H5a2 2 0 00-2 2v4a2 2 0 002 2h2m2 4h6a2 2 0 002-2v-4a2 2 0 00-2-2H9a2 2 0 00-2 2v4a2 2 0 002 2zm8-12V5a2 2 0 00-2-2H9a2 2 0 00-2 2v4h10z"/></svg>In đơn</button>
        <a href="{{ route('backend.orders.index') }}" class="btn btn-ghost btn-sm">Quay lại</a>
    </div>
</div>

<div class="grid grid-cols-1 lg:grid-cols-3 gap-5">
    <div class="lg:col-span-2 space-y-5">
        {{-- Products --}}
        <div class="card bg-base-100 shadow-sm border border-base-200">
            <div class="card-body p-5">
                <h2 class="font-bold text-base-content mb-4">Sản phẩm đã đặt</h2>
                @php $items=[['name'=>'iPhone 15 Pro Max 256GB','sku'=>'IP15PM-256','qty'=>1,'price'=>'29,990,000'],['name'=>'Ốp lưng Silicon','sku'=>'CASE-001','qty'=>2,'price'=>'290,000']]; @endphp
                <div class="overflow-x-auto"><table class="table table-sm"><thead><tr class="border-b border-base-200 text-xs text-base-content/50 uppercase"><th>Sản phẩm</th><th>SKU</th><th class="text-center">SL</th><th class="text-right">Đơn giá</th><th class="text-right">Thành tiền</th></tr></thead><tbody>
                @foreach($items as $item)
                <tr class="border-b border-base-100 last:border-0"><td class="font-medium text-sm">{{ $item['name'] }}</td><td class="font-mono text-xs text-base-content/60">{{ $item['sku'] }}</td><td class="text-center text-sm">{{ $item['qty'] }}</td><td class="text-right text-sm">₫ {{ $item['price'] }}</td><td class="text-right font-semibold text-sm text-primary">₫ {{ $item['price'] }}</td></tr>
                @endforeach
                </tbody></table></div>
                <div class="border-t border-base-200 mt-3 pt-3 space-y-1.5 text-sm">
                    <div class="flex justify-between"><span class="text-base-content/60">Tạm tính</span><span>₫ 30,570,000</span></div>
                    <div class="flex justify-between"><span class="text-base-content/60">Phí ship</span><span>₫ 30,000</span></div>
                    <div class="flex justify-between"><span class="text-base-content/60">Giảm giá</span><span class="text-success">-₫ 150,000</span></div>
                    <div class="flex justify-between font-bold text-base border-t border-base-200 pt-2 mt-2"><span>Tổng cộng</span><span class="text-primary">₫ 30,450,000</span></div>
                </div>
            </div>
        </div>
        {{-- Timeline --}}
        <div class="card bg-base-100 shadow-sm border border-base-200">
            <div class="card-body p-5">
                <h2 class="font-bold text-base-content mb-4">Lịch sử đơn hàng</h2>
                <ol class="relative border-l border-base-200 ml-3 space-y-4">
                    @foreach([['color'=>'bg-success','label'=>'Đã giao hàng','time'=>'13/05/2025 14:30','note'=>'Giao thành công, khách đã nhận'],['color'=>'bg-info','label'=>'Đang vận chuyển','time'=>'12/05/2025 08:00','note'=>'Đối tác giao hàng: GHN'],['color'=>'bg-warning','label'=>'Đã xác nhận','time'=>'11/05/2025 10:25','note'=>'Admin đã xác nhận đơn'],['color'=>'bg-base-300','label'=>'Đặt hàng','time'=>'11/05/2025 10:20','note'=>'Khách đặt hàng thành công']] as $ev)
                    <li class="ml-5"><span class="absolute -left-2.5 flex items-center justify-center w-5 h-5 rounded-full {{ $ev['color'] }}"><svg class="w-2.5 h-2.5 text-white" fill="currentColor" viewBox="0 0 8 8"><circle cx="4" cy="4" r="3"/></svg></span><div><p class="text-sm font-semibold text-base-content">{{ $ev['label'] }}</p><p class="text-xs text-base-content/50">{{ $ev['time'] }} · {{ $ev['note'] }}</p></div></li>
                    @endforeach
                </ol>
            </div>
        </div>
    </div>
    <div class="space-y-5">
        {{-- Status --}}
        <div class="card bg-base-100 shadow-sm border border-base-200">
            <div class="card-body p-5">
                <h2 class="font-bold text-base-content mb-3">Trạng thái</h2>
                <span class="badge badge-success badge-lg mb-3">Hoàn thành</span>
                <select class="select select-bordered select-sm w-full mb-3"><option>Hoàn thành</option><option>Đang giao</option><option>Chờ xử lý</option><option>Đã huỷ</option></select>
                <button class="btn btn-primary btn-sm w-full">Cập nhật</button>
            </div>
        </div>
        {{-- Customer --}}
        <div class="card bg-base-100 shadow-sm border border-base-200">
            <div class="card-body p-5">
                <h2 class="font-bold text-base-content mb-3">Khách hàng</h2>
                <div class="flex items-center gap-3 mb-3"><div class="avatar placeholder"><div class="w-10 h-10 rounded-full bg-primary text-primary-content font-bold"><span>N</span></div></div><div><p class="font-semibold text-sm">Nguyễn Văn A</p><p class="text-xs text-base-content/50">nguyenvana@gmail.com</p></div></div>
                <div class="text-xs space-y-1 text-base-content/60"><p>📞 0901 234 567</p><p>📍 123 Nguyễn Huệ, Q.1, TP.HCM</p></div>
            </div>
        </div>
        {{-- Payment --}}
        <div class="card bg-base-100 shadow-sm border border-base-200">
            <div class="card-body p-5">
                <h2 class="font-bold text-base-content mb-3">Thanh toán</h2>
                <div class="space-y-1.5 text-sm"><div class="flex justify-between"><span class="text-base-content/60">Phương thức</span><span class="badge badge-ghost badge-sm">COD</span></div><div class="flex justify-between"><span class="text-base-content/60">Trạng thái</span><span class="badge badge-success badge-sm">Đã thanh toán</span></div></div>
            </div>
        </div>
    </div>
</div>
@endsection
