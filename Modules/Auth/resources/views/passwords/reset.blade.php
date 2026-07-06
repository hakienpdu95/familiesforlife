@extends('layouts.auth')

@section('title', 'Đặt lại mật khẩu')

@section('content')
<div class="w-full max-w-sm">
    <div class="card bg-base-100 shadow-xl">
        <div class="card-body gap-4">

            <div class="text-center">
                <h1 class="text-2xl font-bold text-primary">Đặt lại mật khẩu</h1>
                <p class="text-base-content/60 text-sm mt-1">Nhập mật khẩu mới của bạn</p>
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

            <form method="POST" action="{{ route('password.update') }}" class="flex flex-col gap-3">
                @csrf

                <input type="hidden" name="token" value="{{ $request->route('token') }}" />

                <label class="form-control w-full">
                    <div class="label">
                        <span class="label-text font-medium">Email</span>
                    </div>
                    <input
                        type="email"
                        name="email"
                        value="{{ old('email', $request->email) }}"
                        placeholder="you@example.com"
                        class="input input-bordered w-full @error('email') input-error @enderror"
                        required autofocus autocomplete="username"
                    />
                    @error('email')
                        <div class="label">
                            <span class="label-text-alt text-error">{{ $message }}</span>
                        </div>
                    @enderror
                </label>

                <label class="form-control w-full">
                    <div class="label">
                        <span class="label-text font-medium">Mật khẩu mới</span>
                    </div>
                    <input
                        type="password"
                        name="password"
                        placeholder="Tối thiểu 8 ký tự"
                        class="input input-bordered w-full @error('password') input-error @enderror"
                        required autocomplete="new-password"
                    />
                    @error('password')
                        <div class="label">
                            <span class="label-text-alt text-error">{{ $message }}</span>
                        </div>
                    @enderror
                </label>

                <label class="form-control w-full">
                    <div class="label">
                        <span class="label-text font-medium">Xác nhận mật khẩu mới</span>
                    </div>
                    <input
                        type="password"
                        name="password_confirmation"
                        placeholder="Nhập lại mật khẩu"
                        class="input input-bordered w-full"
                        required autocomplete="new-password"
                    />
                </label>

                <button type="submit" class="btn btn-primary w-full mt-1">
                    Đặt lại mật khẩu
                </button>
            </form>

        </div>
    </div>
</div>
@endsection
