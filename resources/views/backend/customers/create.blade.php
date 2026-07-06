@extends('layouts.backend')
@section('title','Thêm khách hàng')
@section('breadcrumb')
<nav class="breadcrumb-nav"><a href="{{ route('backend.dashboard') }}">Trang chủ</a><span class="sep">›</span><a href="{{ route('backend.customers.index') }}">Khách hàng</a><span class="sep">›</span><span class="current">Thêm mới</span></nav>
@endsection
@section('content')
<div class="flex flex-wrap items-center justify-between gap-3 mb-6"><div><h1 class="text-2xl font-bold text-base-content">Thêm khách hàng</h1></div><a href="{{ route('backend.customers.index') }}" class="btn btn-ghost btn-sm">Quay lại</a></div>
<div class="grid grid-cols-1 lg:grid-cols-3 gap-5">
    <div class="lg:col-span-2">
        <form action="{{ route('backend.customers.store') }}" method="POST">
        @csrf
        <div class="card bg-base-100 shadow-sm border border-base-200">
            <div class="card-body p-5">
                <h2 class="font-bold text-base-content mb-4">Thông tin cá nhân</h2>
                <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                    <div class="form-control"><label class="label pb-1"><span class="label-text font-semibold">Họ tên <span class="text-error">*</span></span></label><input type="text" name="name" class="input input-bordered @error('name') input-error @enderror" placeholder="Nguyễn Văn A" value="{{ old('name') }}" required>@error('name')<span class="text-error text-xs">{{ $message }}</span>@enderror</div>
                    <div class="form-control"><label class="label pb-1"><span class="label-text font-semibold">Email <span class="text-error">*</span></span></label><input type="email" name="email" class="input input-bordered @error('email') input-error @enderror" placeholder="email@example.com" value="{{ old('email') }}" required>@error('email')<span class="text-error text-xs">{{ $message }}</span>@enderror</div>
                    <div class="form-control"><label class="label pb-1"><span class="label-text font-semibold">Số điện thoại</span></label><input type="tel" name="phone" class="input input-bordered" placeholder="0901 234 567" value="{{ old('phone') }}"></div>
                    <div class="form-control"><label class="label pb-1"><span class="label-text font-semibold">Ngày sinh</span></label><input type="text" name="dob" id="dob-picker" class="input input-bordered" placeholder="dd/mm/yyyy" value="{{ old('dob') }}"></div>
                    <div class="form-control sm:col-span-2"><label class="label pb-1"><span class="label-text font-semibold">Địa chỉ</span></label><input type="text" name="address" class="input input-bordered" placeholder="Số nhà, đường, phường/xã..." value="{{ old('address') }}"></div>
                    <div class="form-control"><label class="label pb-1"><span class="label-text font-semibold">Tỉnh/TP</span></label><select name="province" class="select select-bordered"><option value="">Chọn tỉnh/thành</option><option>TP. Hồ Chí Minh</option><option>Hà Nội</option><option>Đà Nẵng</option></select></div>
                    <div class="form-control"><label class="label pb-1"><span class="label-text font-semibold">Mật khẩu</span></label><input type="password" name="password" class="input input-bordered" placeholder="Để trống = tự tạo"></div>
                </div>
                <div class="mt-4 flex gap-2 items-center">
                    <label class="cursor-pointer flex items-center gap-2"><input type="checkbox" name="send_welcome" class="checkbox checkbox-sm" value="1" checked><span class="label-text">Gửi email chào mừng</span></label>
                </div>
                <div class="mt-4 flex justify-end gap-2">
                    <a href="{{ route('backend.customers.index') }}" class="btn btn-ghost">Huỷ</a>
                    <button type="submit" class="btn btn-primary gap-2"><svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/></svg>Lưu</button>
                </div>
            </div>
        </div>
        </form>
    </div>
    <div>
        <div class="card bg-base-100 shadow-sm border border-base-200"><div class="card-body p-5"><h2 class="font-bold text-base-content mb-3">Nhóm khách hàng</h2><div class="space-y-2">@foreach(['VIP','Thân thiết','Mới','Bán buôn'] as $g)<label class="cursor-pointer flex items-center gap-2"><input type="checkbox" name="groups[]" class="checkbox checkbox-sm" value="{{ $g }}"><span class="label-text">{{ $g }}</span></label>@endforeach</div></div></div>
    </div>
</div>
@endsection
@push('scripts')
<script>document.addEventListener('DOMContentLoaded',()=>{ if(window.initDatePicker) initDatePicker('#dob-picker'); })</script>
@endpush
