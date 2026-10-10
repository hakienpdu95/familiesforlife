@extends('auth::layouts.split')

@section('title', 'Đăng nhập')

@section('heading', 'Chào mừng bạn quay lại')

@section('subheading', 'Vui lòng đăng nhập để tiếp tục vào Hệ thống Quản trị Nội dung.')

@section('form')
@php($rememberedEmail = request()->cookie('pref_email'))
<form method="POST" action="{{ route('login') }}" class="flex flex-col gap-5">
    @csrf

    <x-auth::field name="email" type="email" label="Email" :value="$rememberedEmail" placeholder="you@example.com" :autofocus="! $rememberedEmail" autocomplete="username" />

    <x-auth::password-field name="password" label="Mật khẩu" placeholder="••••••••" :autofocus="(bool) $rememberedEmail" autocomplete="current-password" />

    <div class="mt-2 flex items-center justify-between gap-3">
        <label class="label cursor-pointer gap-2 text-sm text-base-content">
            <input
                type="checkbox"
                name="remember"
                value="1"
                class="checkbox checkbox-sm checkbox-primary"
                {{ old('remember', request()->cookie('pref_remember')) ? 'checked' : '' }}
            />
            Tự động đăng nhập
        </label>
        @if (Route::has('password.request'))
            <a href="{{ route('password.request') }}" class="link link-primary link-hover text-sm font-medium">
                Bạn quên mật khẩu?
            </a>
        @endif
    </div>

    @if (\Modules\Auth\Fortify\ValidateTurnstile::isActive())
        <div class="flex flex-col gap-1">
            <x-turnstile class="w-full" />
            @error('cf-turnstile-response')
                <p class="text-xs text-error">{{ $message }}</p>
            @enderror
        </div>
    @endif

    <button type="submit" class="btn btn-primary btn-lg btn-block font-semibold uppercase tracking-wide">
        Đăng nhập
    </button>

    <div class="flex flex-col gap-2 text-xs leading-relaxed text-base-content/75">
        <p>
            Bằng việc đăng nhập, bạn xác nhận đã đọc và đồng ý với
            <a href="#" class="link link-primary link-hover">Điều khoản dịch vụ</a>
            của chúng tôi.
        </p>
        <p>
            Bạn đồng ý cho phép chúng tôi xử lý dữ liệu cá nhân theo
            <a href="#" class="link link-primary link-hover">Chính sách bảo mật</a>.
        </p>
    </div>
</form>

@if (config('services.google.client_id') || config('services.facebook.client_id') || config('services.linkedin-openid.client_id'))
    <div class="divider my-6 text-xs text-base-content/50">Hoặc tiếp tục với</div>

    <div class="grid gap-2 sm:grid-cols-[repeat(auto-fit,minmax(0,1fr))]">
        @if (config('services.google.client_id'))
            <a href="{{ route('auth.social.redirect', 'google') }}" class="btn btn-outline border-base-300 gap-2" title="Đăng nhập với Google">
                <svg class="size-5" viewBox="0 0 24 24">
                    <path fill="#4285F4" d="M22.56 12.25c0-.78-.07-1.53-.2-2.25H12v4.26h5.92c-.26 1.37-1.04 2.53-2.21 3.31v2.77h3.57c2.08-1.92 3.28-4.74 3.28-8.09z"/>
                    <path fill="#34A853" d="M12 23c2.97 0 5.46-.98 7.28-2.66l-3.57-2.77c-.98.66-2.23 1.06-3.71 1.06-2.86 0-5.29-1.93-6.16-4.53H2.18v2.84C3.99 20.53 7.7 23 12 23z"/>
                    <path fill="#FBBC05" d="M5.84 14.09c-.22-.66-.35-1.36-.35-2.09s.13-1.43.35-2.09V7.07H2.18C1.43 8.55 1 10.22 1 12s.43 3.45 1.18 4.93l2.85-2.22.81-.62z"/>
                    <path fill="#EA4335" d="M12 5.38c1.62 0 3.06.56 4.21 1.64l3.15-3.15C17.45 2.09 14.97 1 12 1 7.7 1 3.99 3.47 2.18 7.07l3.66 2.84c.87-2.6 3.3-4.53 6.16-4.53z"/>
                </svg>
                Google
            </a>
        @endif

        @if (config('services.facebook.client_id'))
            <a href="{{ route('auth.social.redirect', 'facebook') }}" class="btn btn-outline border-base-300 gap-2" title="Đăng nhập với Facebook">
                <svg class="size-5" viewBox="0 0 24 24">
                    <path fill="#1877F2" d="M24 12.073C24 5.405 18.627 0 12 0S0 5.405 0 12.073C0 18.1 4.388 23.094 10.125 24v-8.437H7.078v-3.49h3.047V9.41c0-3.025 1.792-4.697 4.533-4.697 1.312 0 2.686.236 2.686.236v2.97h-1.513c-1.491 0-1.956.93-1.956 1.874v2.25h3.328l-.532 3.49h-2.796V24C19.612 23.094 24 18.1 24 12.073z"/>
                </svg>
                Facebook
            </a>
        @endif

        @if (config('services.linkedin-openid.client_id'))
            <a href="{{ route('auth.social.redirect', 'linkedin') }}" class="btn btn-outline border-base-300 gap-2" title="Đăng nhập với LinkedIn">
                <svg class="size-5" viewBox="0 0 24 24">
                    <path fill="#0A66C2" d="M20.447 20.452h-3.554v-5.569c0-1.328-.027-3.037-1.852-3.037-1.853 0-2.136 1.445-2.136 2.939v5.667H9.351V9h3.414v1.561h.046c.477-.9 1.637-1.85 3.37-1.85 3.601 0 4.267 2.37 4.267 5.455v6.286zM5.337 7.433a2.062 2.062 0 0 1-2.063-2.065 2.064 2.064 0 1 1 2.063 2.065zm1.782 13.019H3.555V9h3.564v11.452zM22.225 0H1.771C.792 0 0 .774 0 1.729v20.542C0 23.227.792 24 1.771 24h20.451C23.2 24 24 23.227 24 22.271V1.729C24 .774 23.2 0 22.222 0h.003z"/>
                </svg>
                LinkedIn
            </a>
        @endif
    </div>
@endif
@endsection
