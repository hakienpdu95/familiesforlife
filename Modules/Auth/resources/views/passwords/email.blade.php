@extends('auth::layouts.split')

@section('title', __('Quên mật khẩu'))

@section('heading', __('Quên mật khẩu?'))

@section('subheading', __('Nhập email đã đăng ký, chúng tôi sẽ gửi cho bạn liên kết để đặt lại mật khẩu.'))

@section('form')
<form method="POST" action="{{ route('password.email') }}" class="flex flex-col gap-5">
    @csrf

    <x-auth::field name="email" type="email" label="Email" placeholder="you@example.com" autofocus autocomplete="username" />

    <button type="submit" class="btn btn-primary btn-lg btn-block font-semibold uppercase tracking-wide">
        {{ __('Gửi liên kết đặt lại') }}
    </button>
</form>

<p class="mt-6 text-center text-sm text-base-content/75">
    {{ __('Bạn đã nhớ mật khẩu?') }}
    <a href="{{ route('login') }}" class="link link-primary link-hover font-medium">{{ __('Quay lại đăng nhập') }}</a>
</p>
@endsection
