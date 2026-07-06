@extends('layouts.auth')

@section('title', 'Đăng ký tổ chức')

@section('content')
<div class="w-full max-w-md">
    <div class="card bg-base-100 shadow-xl">
        <div class="card-body gap-4">

            <div class="text-center">
                <h1 class="text-2xl font-bold text-primary">{{ config('app.name') }}</h1>
                <p class="text-base-content/60 text-sm mt-1">Tạo tổ chức & tài khoản quản trị</p>
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

            <form method="POST" action="{{ route('register') }}" class="flex flex-col gap-3">
                @csrf

                <div class="divider divider-start text-sm font-semibold text-base-content/70">
                    Thông tin tổ chức
                </div>

                <label class="form-control w-full">
                    <div class="label">
                        <span class="label-text font-medium">Tên tổ chức <span class="text-error">*</span></span>
                    </div>
                    <input
                        type="text"
                        name="organization_name"
                        value="{{ old('organization_name') }}"
                        placeholder="VD: Công ty TNHH ABC"
                        class="input input-bordered w-full @error('organization_name') input-error @enderror"
                        required
                    />
                    @error('organization_name')
                        <div class="label">
                            <span class="label-text-alt text-error">{{ $message }}</span>
                        </div>
                    @enderror
                </label>

                <div class="divider divider-start text-sm font-semibold text-base-content/70 mt-1">
                    Tài khoản chủ sở hữu (CEO)
                </div>

                <label class="form-control w-full">
                    <div class="label">
                        <span class="label-text font-medium">Họ và tên <span class="text-error">*</span></span>
                    </div>
                    <input
                        type="text"
                        name="name"
                        value="{{ old('name') }}"
                        placeholder="Nguyễn Văn A"
                        class="input input-bordered w-full @error('name') input-error @enderror"
                        required autofocus autocomplete="name"
                    />
                    @error('name')
                        <div class="label">
                            <span class="label-text-alt text-error">{{ $message }}</span>
                        </div>
                    @enderror
                </label>

                <label class="form-control w-full">
                    <div class="label">
                        <span class="label-text font-medium">Email <span class="text-error">*</span></span>
                    </div>
                    <input
                        type="email"
                        name="email"
                        value="{{ old('email') }}"
                        placeholder="you@company.com"
                        class="input input-bordered w-full @error('email') input-error @enderror"
                        required autocomplete="username"
                    />
                    @error('email')
                        <div class="label">
                            <span class="label-text-alt text-error">{{ $message }}</span>
                        </div>
                    @enderror
                </label>

                <label class="form-control w-full">
                    <div class="label">
                        <span class="label-text font-medium">Mật khẩu <span class="text-error">*</span></span>
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
                        <span class="label-text font-medium">Xác nhận mật khẩu <span class="text-error">*</span></span>
                    </div>
                    <input
                        type="password"
                        name="password_confirmation"
                        placeholder="Nhập lại mật khẩu"
                        class="input input-bordered w-full"
                        required autocomplete="new-password"
                    />
                </label>

                <button type="submit" class="btn btn-primary w-full mt-2">
                    Tạo tổ chức & đăng ký
                </button>
            </form>

            <div class="text-center text-sm text-base-content/60">
                Đã có tài khoản?
                <a href="{{ route('login') }}" class="text-primary hover:underline font-medium">Đăng nhập</a>
            </div>

        </div>
    </div>
</div>
@endsection
