@extends('auth::layouts.split')

@section('title', __('Đặt lại mật khẩu'))

@section('heading', __('Đặt lại mật khẩu'))

@section('subheading', __('Tạo mật khẩu mới cho tài khoản của bạn.'))

@section('form')
<form method="POST" action="{{ route('password.update') }}" class="flex flex-col gap-5">
    @csrf

    <input type="hidden" name="token" value="{{ $request->route('token') }}" />

    <x-auth::field name="email" type="email" label="Email" :value="$request->email" placeholder="you@example.com" autofocus autocomplete="username" />

    <x-auth::password-field name="password" :label="__('Mật khẩu mới')" :placeholder="__('Tối thiểu 8 ký tự')" autocomplete="new-password" />

    <x-auth::password-field name="password_confirmation" :label="__('Xác nhận mật khẩu mới')" :placeholder="__('Nhập lại mật khẩu')" autocomplete="new-password" />

    <button type="submit" class="btn btn-primary btn-lg btn-block font-semibold uppercase tracking-wide">
        {{ __('Đặt lại mật khẩu') }}
    </button>
</form>

<p class="mt-6 text-center text-sm text-base-content/75">
    <a href="{{ route('login') }}" class="link link-primary link-hover font-medium">{{ __('Quay lại đăng nhập') }}</a>
</p>
@endsection
