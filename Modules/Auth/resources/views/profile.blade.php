@extends('layouts.backend')

@section('title', 'Hồ sơ cá nhân')

@section('content')

<div class="flex items-center gap-2 text-sm text-base-content/50 mb-6">
    <a href="{{ route('backend.dashboard') }}" class="hover:text-primary">Dashboard</a>
    <svg class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/></svg>
    <span>Hồ sơ cá nhân</span>
</div>

<div class="max-w-2xl space-y-5">

    {{-- ── Identity card ──────────────────────────────────────────────────── --}}
    <div class="card bg-base-100 border border-base-200 shadow-sm">
        <div class="card-body">
            <div class="flex items-center gap-4 mb-5">
                <img src="https://api.dicebear.com/9.x/initials/svg?seed={{ urlencode($user->name ?? 'U') }}&backgroundColor=6366f1&fontFamily=Arial&fontSize=40&fontWeight=700"
                     alt="Avatar" class="w-16 h-16 rounded-full shrink-0">
                <div>
                    <p class="text-lg font-bold text-base-content">{{ $user->name }}</p>
                    <p class="text-sm text-base-content/50">{{ $user->email }}</p>
                    <div class="flex gap-1.5 mt-1">
                        @if($user->trust_level >= 2)
                            <span class="badge badge-info badge-xs">📱 Phone verified</span>
                        @elseif($user->trust_level >= 1)
                            <span class="badge badge-outline badge-xs">✉ Email verified</span>
                        @else
                            <span class="badge badge-ghost badge-xs">Chưa xác minh</span>
                        @endif
                        @if($user->isOrgMember())
                            <span class="badge badge-primary badge-xs">Đang làm việc</span>
                        @else
                            <span class="badge badge-ghost badge-xs">Tự do</span>
                        @endif
                    </div>
                </div>
            </div>

            <h3 class="font-semibold text-sm mb-3">Cập nhật thông tin</h3>

            @if (session('status') === 'profile-information-updated')
                <div class="alert alert-success text-sm mb-3 py-2">
                    <span>Thông tin đã được cập nhật.</span>
                </div>
            @endif

            <form method="POST" action="{{ url('user/profile-information') }}" class="space-y-3">
                @csrf
                @method('PUT')
                <div class="form-control">
                    <label class="label py-1"><span class="label-text font-medium text-sm">Họ và tên <span class="text-error">*</span></span></label>
                    <input type="text" name="name" value="{{ old('name', $user->name) }}"
                           class="input input-bordered @error('name', 'updateProfileInformation') input-error @enderror" required/>
                    @error('name', 'updateProfileInformation')
                    <label class="label py-0"><span class="label-text-alt text-error">{{ $message }}</span></label>
                    @enderror
                </div>
                <div class="form-control">
                    <label class="label py-1"><span class="label-text font-medium text-sm">Email <span class="text-error">*</span></span></label>
                    <input type="email" name="email" value="{{ old('email', $user->email) }}"
                           class="input input-bordered @error('email', 'updateProfileInformation') input-error @enderror" required/>
                    @error('email', 'updateProfileInformation')
                    <label class="label py-0"><span class="label-text-alt text-error">{{ $message }}</span></label>
                    @enderror
                </div>
                <button type="submit" class="btn btn-primary btn-sm">Lưu thay đổi</button>
            </form>
        </div>
    </div>

    {{-- ── Change password ────────────────────────────────────────────────── --}}
    <div class="card bg-base-100 border border-base-200 shadow-sm">
        <div class="card-body">
            <h3 class="font-semibold text-sm mb-3">Đổi mật khẩu</h3>

            @if (session('status') === 'password-updated')
                <div class="alert alert-success text-sm mb-3 py-2">
                    <span>Mật khẩu đã được cập nhật.</span>
                </div>
            @endif

            <form method="POST" action="{{ url('user/password') }}" class="space-y-3">
                @csrf
                @method('PUT')
                <div class="form-control">
                    <label class="label py-1"><span class="label-text font-medium text-sm">Mật khẩu hiện tại</span></label>
                    <input type="password" name="current_password"
                           class="input input-bordered @error('current_password', 'updatePassword') input-error @enderror"/>
                    @error('current_password', 'updatePassword')
                    <label class="label py-0"><span class="label-text-alt text-error">{{ $message }}</span></label>
                    @enderror
                </div>
                <div class="form-control">
                    <label class="label py-1"><span class="label-text font-medium text-sm">Mật khẩu mới</span></label>
                    <input type="password" name="password"
                           class="input input-bordered @error('password', 'updatePassword') input-error @enderror"/>
                    @error('password', 'updatePassword')
                    <label class="label py-0"><span class="label-text-alt text-error">{{ $message }}</span></label>
                    @enderror
                </div>
                <div class="form-control">
                    <label class="label py-1"><span class="label-text font-medium text-sm">Xác nhận mật khẩu mới</span></label>
                    <input type="password" name="password_confirmation" class="input input-bordered"/>
                </div>
                <button type="submit" class="btn btn-outline btn-primary btn-sm">Đổi mật khẩu</button>
            </form>
        </div>
    </div>

    {{-- ── Linked Social Accounts ────────────────────────────────────────── --}}
    <div class="card bg-base-100 border border-base-200 shadow-sm">
        <div class="card-body">
            <h3 class="font-semibold text-sm mb-3">Tài khoản liên kết</h3>

            @if ($errors->has('social'))
                <div class="alert alert-error text-sm mb-3 py-2">
                    <span>{{ $errors->first('social') }}</span>
                </div>
            @endif

            @if (session('social_success'))
                <div class="alert alert-success text-sm mb-3 py-2">
                    <span>{{ session('social_success') }}</span>
                </div>
            @endif

            @foreach (['google' => 'Google', 'facebook' => 'Facebook', 'linkedin' => 'LinkedIn'] as $provider => $label)
                @php $linked = $user->socialAccounts->firstWhere('provider', $provider) @endphp

                <div class="flex items-center justify-between py-2 border-b border-base-200 last:border-0">
                    <span class="font-medium text-sm">{{ $label }}</span>

                    @if ($linked)
                        <div class="flex items-center gap-3">
                            <span class="text-xs text-base-content/50">{{ $linked->provider_email }}</span>
                            <form method="POST"
                                  action="{{ route('auth.social.unlink', $provider) }}"
                                  onsubmit="return confirm('Bỏ liên kết {{ $label }}?')">
                                @csrf
                                @method('DELETE')
                                <button type="submit" class="btn btn-xs btn-ghost text-error">Bỏ liên kết</button>
                            </form>
                        </div>
                    @else
                        <a href="{{ route('auth.social.redirect', $provider) }}"
                           class="btn btn-xs btn-outline">
                            Kết nối
                        </a>
                    @endif
                </div>
            @endforeach
        </div>
    </div>

    {{-- ── Quick links ─────────────────────────────────────────────────────── --}}
    <div class="card bg-base-100 border border-base-200 shadow-sm">
        <div class="card-body">
            <h3 class="font-semibold text-sm mb-3">Liên kết nhanh</h3>
            <div class="flex flex-wrap gap-2">
                <a href="{{ route('passport.index') }}" class="btn btn-ghost btn-sm gap-1.5">
                    <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6.253v13m0-13C10.832 5.477 9.246 5 7.5 5S4.168 5.477 3 6.253v13C4.168 18.477 5.754 18 7.5 18s3.332.477 4.5 1.253m0-13C13.168 5.477 14.754 5 16.5 5c1.747 0 3.332.477 4.5 1.253v13C19.832 18.477 18.247 18 16.5 18c-1.746 0-3.332.477-4.5 1.253"/></svg>
                    Career Journal
                </a>
                <a href="{{ route('passport.verify.index') }}" class="btn btn-ghost btn-sm gap-1.5">
                    <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z"/></svg>
                    Xác minh danh tính
                </a>
                @if($user->isOrgMember())
                <a href="{{ route('backend.workforce.me') }}" class="btn btn-ghost btn-sm gap-1.5">
                    <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5.121 17.804A13.937 13.937 0 0112 16c2.5 0 4.847.655 6.879 1.804M15 10a3 3 0 11-6 0 3 3 0 016 0zm6 2a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                    Hồ sơ Digital Twin
                </a>
                @endif
                <a href="{{ route('campaigns.index') }}" class="btn btn-ghost btn-sm gap-1.5">
                    <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 12l2-2m0 0l7-7 7 7M5 10v10a1 1 0 001 1h3m10-11l2 2m-2-2v10a1 1 0 01-1 1h-3m-6 0a1 1 0 001-1v-4a1 1 0 011-1h2a1 1 0 011 1v4a1 1 0 001 1m-6 0h6"/></svg>
                    Assessment Marketplace
                </a>
            </div>
        </div>
    </div>

</div>

@endsection
