@extends('layouts.auth')
@section('title', 'Xác minh email')

@section('content')
<div class="w-full max-w-md mx-auto">

    <div class="text-center mb-8">
        <div class="inline-flex items-center justify-center w-16 h-16 rounded-full bg-primary/10 mb-4">
            <svg class="w-8 h-8 text-primary" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M3 8l7.89 5.26a2 2 0 002.22 0L21 8M5 19h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z"/>
            </svg>
        </div>
        <h1 class="text-2xl font-bold">Xác minh email của bạn</h1>
        <p class="text-base-content/60 text-sm mt-1">{{ config('app.name') }}</p>
    </div>

    <div class="card bg-base-100 shadow-sm border border-base-200">
        <div class="card-body gap-4">

            @if(session('status') === 'verification-link-sent')
            <div class="alert alert-success">
                <svg class="w-5 h-5 shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/>
                </svg>
                <span>Email xác minh đã được gửi lại thành công! Kiểm tra hộp thư của bạn.</span>
            </div>
            @endif

            <p class="text-sm text-base-content/70 leading-relaxed">
                Chúng tôi đã gửi một link xác minh đến
                <span class="font-semibold text-base-content">{{ auth()->user()->email }}</span>.
                Vui lòng kiểm tra hộp thư và nhấp vào link để kích hoạt tài khoản.
            </p>

            <p class="text-sm text-base-content/50">
                Kiểm tra cả thư mục <strong>Spam / Junk</strong> nếu không thấy email.
            </p>

            <div class="divider text-xs text-base-content/30 my-1">Chưa nhận được email?</div>

            <form method="POST" action="{{ route('verification.send') }}">
                @csrf
                <button type="submit" class="btn btn-primary w-full gap-2">
                    <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"/>
                    </svg>
                    Gửi lại email xác minh
                </button>
            </form>

            <form method="POST" action="{{ route('logout') }}">
                @csrf
                <button type="submit" class="btn btn-ghost btn-sm w-full text-base-content/40">
                    Đăng xuất
                </button>
            </form>

        </div>
    </div>

</div>
@endsection
