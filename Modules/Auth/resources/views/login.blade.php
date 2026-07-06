@extends('layouts.auth')

@section('title', 'Đăng nhập')

@section('content')
<div class="w-full max-w-sm">
    <div class="card bg-base-100 shadow-xl">
        <div class="card-body gap-4">

            {{-- Logo --}}
            <div class="text-center">
                <h1 class="text-2xl font-bold text-primary">{{ config('app.name') }}</h1>
                <p class="text-base-content/60 text-sm mt-1">Đăng nhập vào hệ thống</p>
            </div>

            {{-- Session status --}}
            @if (session('status'))
                <div class="alert alert-success text-sm">
                    <span>{{ session('status') }}</span>
                </div>
            @endif

            {{-- Validation errors --}}
            @if ($errors->any())
                <div class="alert alert-error text-sm">
                    <ul class="list-disc list-inside space-y-0.5">
                        @foreach ($errors->all() as $error)
                            <li>{{ $error }}</li>
                        @endforeach
                    </ul>
                </div>
            @endif

            <form method="POST" action="{{ route('login') }}" class="flex flex-col gap-3">
                @csrf

                {{-- Email --}}
                <label class="form-control w-full">
                    <div class="label pb-1">
                        <span class="label-text font-medium">Email</span>
                    </div>
                    <input
                        type="email"
                        name="email"
                        value="{{ old('email') }}"
                        placeholder="you@example.com"
                        class="input input-bordered w-full @error('email') input-error @enderror"
                        required
                        autofocus
                        autocomplete="username"
                    />
                </label>

                {{-- Password --}}
                <div class="form-control w-full" x-data="{ show: false }">
                    <div class="label pb-1">
                        <span class="label-text font-medium">Mật khẩu</span>
                        @if (Route::has('password.request'))
                            <a href="{{ route('password.request') }}" class="label-text-alt text-primary hover:underline">
                                Quên mật khẩu?
                            </a>
                        @endif
                    </div>
                    <label class="input input-bordered flex items-center w-full gap-0 @error('password') input-error @enderror">
                        <input
                            :type="show ? 'text' : 'password'"
                            name="password"
                            placeholder="••••••••"
                            class="grow min-w-0"
                            required
                            autocomplete="current-password"
                        />
                        <button type="button"
                                @click="show = !show"
                                tabindex="-1"
                                class="flex items-center justify-center shrink-0 w-8 h-8 rounded text-base-content/40 hover:text-base-content transition-colors"
                                :aria-label="show ? 'Ẩn mật khẩu' : 'Hiện mật khẩu'">
                            <svg x-show="!show" class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/>
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"/>
                            </svg>
                            <svg x-show="show" class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13.875 18.825A10.05 10.05 0 0112 19c-4.478 0-8.268-2.943-9.543-7a9.97 9.97 0 011.563-3.029m5.858.908a3 3 0 114.243 4.243M9.878 9.878l4.242 4.242M9.88 9.88l-3.29-3.29m7.532 7.532l3.29 3.29M3 3l3.59 3.59m0 0A9.953 9.953 0 0112 5c4.478 0 8.268 2.943 9.543 7a10.025 10.025 0 01-4.132 4.411m0 0L21 21"/>
                            </svg>
                        </button>
                    </label>
                </div>

                {{-- Remember me --}}
                <label class="label cursor-pointer justify-start gap-3 py-0">
                    <input type="checkbox" name="remember" value="1"
                           class="checkbox checkbox-sm checkbox-primary"
                           {{ old('remember', request()->cookie('pref_remember')) ? 'checked' : '' }} />
                    <span class="label-text text-sm">Ghi nhớ đăng nhập</span>
                </label>

                {{-- Cloudflare Turnstile — hiện khi keys đã cấu hình (non-local) --}}
                @if (\Modules\Auth\Fortify\ValidateTurnstile::isActive())
                    <div class="flex flex-col gap-1">
                        <x-turnstile class="w-full" />
                        @error('cf-turnstile-response')
                            <p class="text-error text-xs">{{ $message }}</p>
                        @enderror
                    </div>
                @endif

                <button type="submit" class="btn btn-primary w-full mt-1">
                    Đăng nhập
                </button>
            </form>

            {{-- Social Login — hiện khi ít nhất 1 provider được cấu hình --}}
            @if (config('services.google.client_id') || config('services.facebook.client_id') || config('services.linkedin-openid.client_id'))
                <div class="divider text-xs text-base-content/40 my-0">Hoặc tiếp tục với</div>

                <div class="flex flex-col gap-2">
                    @if (config('services.google.client_id'))
                    <a href="{{ route('auth.social.redirect', 'google') }}"
                       class="btn btn-outline gap-2 w-full">
                        <svg class="w-5 h-5" viewBox="0 0 24 24">
                            <path fill="#4285F4" d="M22.56 12.25c0-.78-.07-1.53-.2-2.25H12v4.26h5.92c-.26 1.37-1.04 2.53-2.21 3.31v2.77h3.57c2.08-1.92 3.28-4.74 3.28-8.09z"/>
                            <path fill="#34A853" d="M12 23c2.97 0 5.46-.98 7.28-2.66l-3.57-2.77c-.98.66-2.23 1.06-3.71 1.06-2.86 0-5.29-1.93-6.16-4.53H2.18v2.84C3.99 20.53 7.7 23 12 23z"/>
                            <path fill="#FBBC05" d="M5.84 14.09c-.22-.66-.35-1.36-.35-2.09s.13-1.43.35-2.09V7.07H2.18C1.43 8.55 1 10.22 1 12s.43 3.45 1.18 4.93l2.85-2.22.81-.62z"/>
                            <path fill="#EA4335" d="M12 5.38c1.62 0 3.06.56 4.21 1.64l3.15-3.15C17.45 2.09 14.97 1 12 1 7.7 1 3.99 3.47 2.18 7.07l3.66 2.84c.87-2.6 3.3-4.53 6.16-4.53z"/>
                        </svg>
                        Đăng nhập với Google
                    </a>
                    @endif

                    @if (config('services.facebook.client_id'))
                    <a href="{{ route('auth.social.redirect', 'facebook') }}"
                       class="btn btn-outline gap-2 w-full">
                        <svg class="w-5 h-5" viewBox="0 0 24 24">
                            <path fill="#1877F2" d="M24 12.073C24 5.405 18.627 0 12 0S0 5.405 0 12.073C0 18.1 4.388 23.094 10.125 24v-8.437H7.078v-3.49h3.047V9.41c0-3.025 1.792-4.697 4.533-4.697 1.312 0 2.686.236 2.686.236v2.97h-1.513c-1.491 0-1.956.93-1.956 1.874v2.25h3.328l-.532 3.49h-2.796V24C19.612 23.094 24 18.1 24 12.073z"/>
                        </svg>
                        Đăng nhập với Facebook
                    </a>
                    @endif

                    @if (config('services.linkedin-openid.client_id'))
                    <a href="{{ route('auth.social.redirect', 'linkedin') }}"
                       class="btn btn-outline gap-2 w-full">
                        <svg class="w-5 h-5" viewBox="0 0 24 24">
                            <path fill="#0A66C2" d="M20.447 20.452h-3.554v-5.569c0-1.328-.027-3.037-1.852-3.037-1.853 0-2.136 1.445-2.136 2.939v5.667H9.351V9h3.414v1.561h.046c.477-.9 1.637-1.85 3.37-1.85 3.601 0 4.267 2.37 4.267 5.455v6.286zM5.337 7.433a2.062 2.062 0 0 1-2.063-2.065 2.064 2.064 0 1 1 2.063 2.065zm1.782 13.019H3.555V9h3.564v11.452zM22.225 0H1.771C.792 0 0 .774 0 1.729v20.542C0 23.227.792 24 1.771 24h20.451C23.2 24 24 23.227 24 22.271V1.729C24 .774 23.2 0 22.222 0h.003z"/>
                        </svg>
                        Đăng nhập với LinkedIn
                    </a>
                    @endif
                </div>
            @endif

        </div>
    </div>
</div>
@endsection
