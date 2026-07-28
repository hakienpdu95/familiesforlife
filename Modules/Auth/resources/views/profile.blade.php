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

    {{-- ── Author Hub — Hồ sơ tác giả công khai ──────────────────────────────
         spec/Author_Contributor_Hub_Technical_Specification.md §6.1 — chỉ hiện nếu user
         isPlatform() (§0 v1.2, loại marketing/Lớp B) VÀ có ít nhất 1 bài viết. --}}
    @if($canShowAuthorCard)
    @php $authorProfile = $user->authorProfile; @endphp
    <div class="card bg-base-100 border border-base-200 shadow-sm">
        <div class="card-body">
            <h3 class="font-semibold text-sm mb-1">Hồ sơ tác giả công khai</h3>
            <p class="text-xs text-base-content/50 mb-3">
                Hiển thị tại trang <code>/tac-gia</code> nếu bạn bật công khai — độc giả xem được
                tiểu sử và danh sách bài bạn đã xuất bản.
            </p>

            @if(session('status') === 'author-profile-updated')
            <div class="alert alert-success text-sm mb-3 py-2"><span>Đã lưu hồ sơ tác giả.</span></div>
            @endif

            <form method="POST" action="{{ route('post.author-hub.profile.update') }}" class="space-y-3">
                @csrf

                <div class="flex items-center gap-4">
                    <img id="author-avatar-preview"
                         src="{{ $authorProfile?->avatarUrl() ?? 'https://api.dicebear.com/9.x/initials/svg?seed=' . urlencode($user->name) . '&backgroundColor=6366f1&fontFamily=Arial&fontSize=40&fontWeight=700' }}"
                         alt="Avatar tác giả" class="w-16 h-16 rounded-full shrink-0 object-cover">
                    <div class="flex-1">
                        <div id="author-avatar-filepond"
                             @if($authorProfile) data-context-type="post_author_profile" data-context-id="{{ $authorProfile->id }}" @endif></div>
                        <input type="hidden" name="avatar_media_uuid" id="author-avatar-media-uuid">
                        @error('avatar_media_uuid', 'authorProfile')<p class="mt-1 text-xs text-error">{{ $message }}</p>@enderror
                    </div>
                </div>

                <div class="form-control">
                    <label class="label py-1"><span class="label-text font-medium text-sm">Bút danh</span></label>
                    <input type="text" name="pen_name" value="{{ old('pen_name', $authorProfile?->pen_name) }}"
                           placeholder="{{ $user->name }}"
                           class="input input-bordered input-sm @error('pen_name', 'authorProfile') input-error @enderror"/>
                    @error('pen_name', 'authorProfile')<label class="label py-0"><span class="label-text-alt text-error">{{ $message }}</span></label>@enderror
                </div>

                <div class="form-control">
                    <label class="label py-1"><span class="label-text font-medium text-sm">Tiểu sử</span></label>
                    <textarea name="bio" rows="3" maxlength="500"
                              class="textarea textarea-bordered text-sm @error('bio', 'authorProfile') textarea-error @enderror">{{ old('bio', $authorProfile?->bio) }}</textarea>
                    @error('bio', 'authorProfile')<label class="label py-0"><span class="label-text-alt text-error">{{ $message }}</span></label>@enderror
                </div>

                <div class="grid sm:grid-cols-2 gap-3">
                    <div class="form-control">
                        <label class="label py-1"><span class="label-text text-xs">Chức danh chuyên môn</span></label>
                        <input type="text" name="job_title" value="{{ old('job_title', $authorProfile?->job_title) }}"
                               placeholder="VD: Bác sĩ Nhi khoa"
                               class="input input-bordered input-sm @error('job_title', 'authorProfile') input-error @enderror"/>
                        @error('job_title', 'authorProfile')<label class="label py-0"><span class="label-text-alt text-error">{{ $message }}</span></label>@enderror
                    </div>
                    <div class="form-control">
                        <label class="label py-1"><span class="label-text text-xs">Bằng cấp/chứng chỉ</span></label>
                        <input type="text" name="credentials" value="{{ old('credentials', $authorProfile?->credentials) }}"
                               placeholder="VD: Thạc sĩ Dinh dưỡng, 10 năm kinh nghiệm"
                               class="input input-bordered input-sm @error('credentials', 'authorProfile') input-error @enderror"/>
                        @error('credentials', 'authorProfile')<label class="label py-0"><span class="label-text-alt text-error">{{ $message }}</span></label>@enderror
                    </div>
                </div>

                <div class="grid sm:grid-cols-2 gap-3">
                    @foreach(['facebook' => 'Facebook', 'x' => 'X', 'linkedin' => 'LinkedIn', 'website' => 'Website'] as $key => $label)
                    <div class="form-control">
                        <label class="label py-1"><span class="label-text text-xs">{{ $label }}</span></label>
                        <input type="url" name="social_links[{{ $key }}]"
                               value="{{ old('social_links.'.$key, $authorProfile?->social_links[$key] ?? '') }}"
                               placeholder="https://..."
                               class="input input-bordered input-sm @error('social_links.'.$key, 'authorProfile') input-error @enderror"/>
                        @error('social_links.'.$key, 'authorProfile')<label class="label py-0"><span class="label-text-alt text-error">{{ $message }}</span></label>@enderror
                    </div>
                    @endforeach
                </div>

                <label class="label cursor-pointer justify-start gap-2 py-1">
                    <input type="checkbox" name="is_public" value="1" class="toggle toggle-sm toggle-primary"
                           {{ old('is_public', $authorProfile?->is_public ?? true) ? 'checked' : '' }}>
                    <span class="label-text text-sm">Hiển thị trang tác giả công khai</span>
                </label>

                <div class="pt-1">
                    <button type="submit" class="btn btn-primary btn-sm">Lưu hồ sơ tác giả</button>
                </div>
            </form>
        </div>
    </div>
    @endif

</div>

@endsection

@push('scripts')
    @vite(['resources/js/modules/filepond.js'], 'build/backend')
    <script>
    document.addEventListener('DOMContentLoaded', () => {
        const el = document.getElementById('author-avatar-filepond');
        if (window.initFilePondUpload && el) {
            initFilePondUpload(el, {
                collection: 'avatar',
                contextType: el.dataset.contextType,
                contextId: el.dataset.contextId,
                bindTo: '#author-avatar-media-uuid',
                onUploaded: (uuid, url) => {
                    document.getElementById('author-avatar-preview').src = url;
                },
            });
        }
    });
    </script>
@endpush
