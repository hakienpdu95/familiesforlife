@extends('auth::layouts.split')

@section('title', 'Đăng ký tổ chức')

@section('heading', 'Tạo tổ chức mới')

@section('subheading', 'Đăng ký tổ chức và tài khoản quản trị để bắt đầu sử dụng Hệ thống Quản trị Nội dung.')

@section('form')
<form method="POST" action="{{ route('register') }}" class="flex flex-col gap-5">
    @csrf

    <div class="divider divider-start my-0 text-sm font-semibold text-base-content/75">Thông tin tổ chức</div>

    <x-auth::field name="organization_name" label="Tên tổ chức" placeholder="VD: Công ty TNHH ABC" autocomplete="organization" />

    <div class="divider divider-start my-0 text-sm font-semibold text-base-content/75">Tài khoản chủ sở hữu (CEO)</div>

    <x-auth::field name="name" label="Họ và tên" placeholder="Nguyễn Văn A" autofocus autocomplete="name" />

    <x-auth::field name="email" type="email" label="Email" placeholder="you@company.com" autocomplete="username" />

    <div class="grid gap-5 sm:grid-cols-2">
        <x-auth::password-field name="password" label="Mật khẩu" placeholder="Tối thiểu 8 ký tự" autocomplete="new-password" />

        <x-auth::password-field name="password_confirmation" label="Xác nhận mật khẩu" placeholder="Nhập lại mật khẩu" autocomplete="new-password" />
    </div>

    <button type="submit" class="btn btn-primary btn-lg btn-block font-semibold uppercase tracking-wide">
        Tạo tổ chức &amp; đăng ký
    </button>

    <p class="text-xs leading-relaxed text-base-content/75">
        Bằng việc đăng ký, bạn đồng ý với
        <a href="#" class="link link-primary link-hover">Điều khoản dịch vụ</a>
        và
        <a href="#" class="link link-primary link-hover">Chính sách bảo mật</a>
        của chúng tôi.
    </p>
</form>

<p class="mt-6 text-center text-sm text-base-content/75">
    Đã có tài khoản?
    <a href="{{ route('login') }}" class="link link-primary link-hover font-medium">Đăng nhập</a>
</p>
@endsection
