@extends('auth::layouts.split')

@section('title', __('Đăng ký tài khoản'))

@section('heading', __('Tạo tài khoản'))

@section('subheading', __('Đăng ký tài khoản để bắt đầu sử dụng hệ thống.'))

@section('form')
<form method="POST" action="{{ route('register') }}" class="flex flex-col gap-5">
    @csrf

    <x-auth::field name="name" :label="__('Họ và tên')" :placeholder="__('Nguyễn Văn A')" autofocus autocomplete="name" />

    <x-auth::field name="email" type="email" label="Email" placeholder="you@company.com" autocomplete="username" />

    <div class="grid gap-5 sm:grid-cols-2">
        <x-auth::password-field name="password" :label="__('Mật khẩu')" :placeholder="__('Tối thiểu 8 ký tự')" autocomplete="new-password" />

        <x-auth::password-field name="password_confirmation" :label="__('Xác nhận mật khẩu')" :placeholder="__('Nhập lại mật khẩu')" autocomplete="new-password" />
    </div>

    <button type="submit" class="btn btn-primary btn-lg btn-block font-semibold uppercase tracking-wide">
        {{ __('Đăng ký') }}
    </button>

    <p class="text-xs leading-relaxed text-base-content/75">
        {{ __('Bằng việc đăng ký, bạn đồng ý với') }}
        <a href="#" class="link link-primary link-hover">{{ __('Điều khoản dịch vụ') }}</a>
        {{ __('và') }}
        <a href="#" class="link link-primary link-hover">{{ __('Chính sách bảo mật') }}</a>
        {{ __('của chúng tôi.') }}
    </p>
</form>

<p class="mt-6 text-center text-sm text-base-content/75">
    {{ __('Đã có tài khoản?') }}
    <a href="{{ route('login') }}" class="link link-primary link-hover font-medium">{{ __('Đăng nhập') }}</a>
</p>
@endsection
