@php
    $account ??= null;
    $currentRole = $account?->roles->first()?->name;
@endphp

<section class="p-6 border-b border-base-200">
    <h2 class="font-semibold text-base-content">Thông tin đăng nhập</h2>
    <p class="text-xs text-base-content/50 mt-0.5 mb-4">Người dùng đăng nhập bằng email hoặc tên đăng nhập.</p>

    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
        <div class="form-control">
            <label for="account-email" class="label py-0 pb-1.5"><span class="label-text font-medium">Email @unless($account)<span class="text-error">*</span>@endunless</span></label>
            @if ($account)
                <input id="account-email" type="email" value="{{ $account->email }}" class="input input-bordered input-sm w-full" disabled>
                <p class="mt-1 text-xs text-base-content/40">Không đổi được email qua đây.</p>
            @else
                <input id="account-email" type="email" name="email" value="{{ old('email') }}" required maxlength="255" autocomplete="off"
                       class="input input-bordered input-sm w-full @error('email') input-error @enderror">
                <p class="mt-1 text-xs text-base-content/40">Link đặt mật khẩu sẽ được gửi tới email này.</p>
            @endif
        </div>

        <div class="form-control">
            <label for="account-username" class="label py-0 pb-1.5"><span class="label-text font-medium">Tên đăng nhập <span class="text-error">*</span></span></label>
            <input id="account-username" type="text" name="username" value="{{ old('username', $account?->username) }}"
                   required minlength="{{ \App\Support\Username::MIN }}" maxlength="{{ \App\Support\Username::MAX }}"
                   pattern="[a-z0-9](?:[a-z0-9._\-]*[a-z0-9])?" autocapitalize="none" autocomplete="off" spellcheck="false"
                   data-suggest-url="{{ route('backend.api.users.username-suggestion') }}"
                   data-ignore-id="{{ $account?->id }}"
                   class="input input-bordered input-sm w-full font-mono @error('username') input-error @enderror">
            <p class="mt-1 text-xs text-base-content/40">{{ \App\Support\Username::MIN }}–{{ \App\Support\Username::MAX }} ký tự: chữ thường a-z, số và . _ -</p>
        </div>
    </div>
</section>

<section class="p-6 border-b border-base-200">
    <h2 class="font-semibold text-base-content">Hồ sơ</h2>
    <p class="text-xs text-base-content/50 mt-0.5 mb-4">Thông tin hiển thị và liên hệ nội bộ.</p>

    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
        <div class="form-control md:col-span-2">
            <label for="account-name" class="label py-0 pb-1.5"><span class="label-text font-medium">Họ và tên <span class="text-error">*</span></span></label>
            <input id="account-name" type="text" name="name" value="{{ old('name', $account?->name) }}" required maxlength="255"
                   class="input input-bordered input-sm w-full @error('name') input-error @enderror">
        </div>

        <div class="form-control">
            <label for="account-phone" class="label py-0 pb-1.5"><span class="label-text font-medium">Số điện thoại</span></label>
            <input id="account-phone" type="tel" name="phone" value="{{ old('phone', $account?->phone) }}" maxlength="20" inputmode="tel" placeholder="0912345678"
                   class="input input-bordered input-sm w-full @error('phone') input-error @enderror">
        </div>

        <div class="form-control">
            <label for="account-job-title" class="label py-0 pb-1.5"><span class="label-text font-medium">Chức danh</span></label>
            <input id="account-job-title" type="text" name="job_title" value="{{ old('job_title', $account?->job_title) }}" maxlength="100" placeholder="VD: Biên tập viên"
                   class="input input-bordered input-sm w-full @error('job_title') input-error @enderror">
        </div>

        <div class="form-control">
            <label for="account-department" class="label py-0 pb-1.5"><span class="label-text font-medium">Phòng ban</span></label>
            <input id="account-department" type="text" name="department" value="{{ old('department', $account?->department) }}" maxlength="100" list="account-departments" placeholder="VD: Ban Biên tập"
                   class="input input-bordered input-sm w-full @error('department') input-error @enderror">
            <datalist id="account-departments">
                @foreach ($departments as $department)
                    <option value="{{ $department }}"></option>
                @endforeach
            </datalist>
        </div>
    </div>
</section>

<section class="p-6 border-b border-base-200">
    <h2 class="font-semibold text-base-content">Phân quyền hệ thống</h2>
    <p class="text-xs text-base-content/50 mt-0.5 mb-4">Vai trò quyết định tài khoản được làm gì trong hệ thống.</p>

    <div class="form-control md:max-w-sm">
        <label for="ts-account-form-role" class="label py-0 pb-1.5"><span class="label-text font-medium">Vai trò <span class="text-error">*</span></span></label>
        <select id="ts-account-form-role" name="role" data-ts-placeholder="— Chọn vai trò —"
                class="select select-bordered select-sm w-full @error('role') select-error @enderror">
            <option value="">— Chọn vai trò —</option>
            @foreach ($labels as $value => $label)
                <option value="{{ $value }}" @selected(old('role', $currentRole) === $value)>{{ $label }}</option>
            @endforeach
        </select>
    </div>
</section>
