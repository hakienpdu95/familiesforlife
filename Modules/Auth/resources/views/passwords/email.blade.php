@extends('layouts.auth')

@section('title', 'Quên mật khẩu')

@section('content')
<div class="w-full max-w-sm">
    <div class="card bg-base-100 shadow-xl">
        <div class="card-body gap-4">

            <div class="text-center">
                <h1 class="text-2xl font-bold text-primary">Quên mật khẩu</h1>
                <p class="text-base-content/60 text-sm mt-1">
                    Nhập email, chúng tôi sẽ gửi link đặt lại mật khẩu.
                </p>
            </div>

            @if ($errors->any())
                <div class="alert alert-error text-sm">
                    <ul class="list-disc list-inside">
                        @foreach ($errors->all() as $error)
                            <li>{{ $error }}</li>
                        @endforeach
                    </ul>
                </div>
            @endif

            <form method="POST" action="{{ route('password.email') }}" class="flex flex-col gap-3">
                @csrf

                <label class="form-control w-full">
                    <div class="label">
                        <span class="label-text font-medium">Email</span>
                    </div>
                    <input
                        type="email"
                        name="email"
                        value="{{ old('email') }}"
                        placeholder="you@example.com"
                        class="input input-bordered w-full @error('email') input-error @enderror"
                        required autofocus autocomplete="username"
                    />
                </label>

                <button type="submit" class="btn btn-primary w-full mt-1">
                    Gửi link đặt lại mật khẩu
                </button>
            </form>

            <div class="text-center text-sm">
                <a href="{{ route('login') }}" class="text-primary hover:underline">
                    ← Quay lại đăng nhập
                </a>
            </div>

        </div>
    </div>
</div>
@endsection
