<!DOCTYPE html>
<html lang="vi" data-theme="light">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>Đăng ký đăng tin tuyển dụng — Marketplace</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'], 'build/backend')
</head>
<body class="bg-base-200 min-h-screen flex items-center justify-center p-4">

<div class="card bg-base-100 shadow-xl w-full max-w-xl">
    <div class="card-body">

        <div class="text-center mb-6">
            <div class="inline-flex items-center justify-center w-14 h-14 bg-primary/10 rounded-full mb-3">
                <svg class="w-7 h-7 text-primary" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4"/>
                </svg>
            </div>
            <h1 class="text-2xl font-bold">Đăng ký đăng tin tuyển dụng</h1>
            <p class="text-sm opacity-60 mt-1">Tạo tài khoản doanh nghiệp để đăng tin lên Marketplace</p>
        </div>

        @if($errors->any())
        <div class="alert alert-error mb-4">
            <ul class="text-sm list-disc list-inside">
                @foreach($errors->all() as $error)
                <li>{{ $error }}</li>
                @endforeach
            </ul>
        </div>
        @endif

        <form action="{{ route('marketplace.employer.register.store') }}" method="POST" class="space-y-4">
            @csrf

            <div class="divider text-xs font-semibold opacity-50">Thông tin doanh nghiệp</div>

            <div class="form-control">
                <label class="label"><span class="label-text font-medium">Tên công ty <span class="text-error">*</span></span></label>
                <input type="text" name="company_name" value="{{ old('company_name') }}" required maxlength="255"
                       placeholder="Công ty TNHH ABC"
                       class="input input-bordered @error('company_name') input-error @enderror">
            </div>

            <div class="form-control">
                <label class="label"><span class="label-text font-medium">Website</span></label>
                <input type="url" name="website" value="{{ old('website') }}" maxlength="300"
                       placeholder="https://company.com"
                       class="input input-bordered @error('website') input-error @enderror">
            </div>

            <div class="form-control">
                <label class="label"><span class="label-text font-medium">Giới thiệu công ty</span></label>
                <textarea name="company_description" rows="3"
                          placeholder="Mô tả ngắn về công ty..."
                          class="textarea textarea-bordered">{{ old('company_description') }}</textarea>
            </div>

            <div class="divider text-xs font-semibold opacity-50">Tài khoản HR</div>

            <div class="form-control">
                <label class="label"><span class="label-text font-medium">Họ tên người liên hệ <span class="text-error">*</span></span></label>
                <input type="text" name="contact_name" value="{{ old('contact_name') }}" required maxlength="255"
                       placeholder="Nguyễn Văn A"
                       class="input input-bordered @error('contact_name') input-error @enderror">
            </div>

            <div class="form-control">
                <label class="label"><span class="label-text font-medium">Email HR <span class="text-error">*</span></span></label>
                <input type="email" name="hr_email" value="{{ old('hr_email') }}" required maxlength="150"
                       placeholder="hr@company.com"
                       class="input input-bordered @error('hr_email') input-error @enderror">
            </div>

            <div class="form-control">
                <label class="label"><span class="label-text font-medium">Mật khẩu <span class="text-error">*</span></span></label>
                <input type="password" name="password" required minlength="8"
                       placeholder="Tối thiểu 8 ký tự"
                       class="input input-bordered @error('password') input-error @enderror">
            </div>

            <div class="divider text-xs font-semibold opacity-50">Tin đăng đầu tiên (tùy chọn)</div>

            <div class="form-control">
                <label class="label"><span class="label-text font-medium">Tiêu đề tin tuyển dụng</span></label>
                <input type="text" name="listing_title" value="{{ old('listing_title') }}" maxlength="300"
                       placeholder="Senior PHP Developer"
                       class="input input-bordered @error('listing_title') input-error @enderror">
            </div>

            <div class="form-control">
                <label class="label"><span class="label-text font-medium">Mô tả vị trí</span></label>
                <textarea name="listing_description" rows="3"
                          placeholder="Mô tả ngắn về vị trí..."
                          class="textarea textarea-bordered">{{ old('listing_description') }}</textarea>
            </div>

            <div class="form-control">
                <label class="label"><span class="label-text font-medium">Địa điểm làm việc</span></label>
                <input type="text" name="listing_location" value="{{ old('listing_location') }}" maxlength="200"
                       placeholder="Hà Nội, Remote..."
                       class="input input-bordered">
            </div>

            <button type="submit" class="btn btn-primary w-full mt-2">
                Đăng ký & gửi duyệt
            </button>

            <p class="text-xs text-center opacity-50 mt-2">
                Sau khi đăng ký, đội ngũ của chúng tôi sẽ xem xét và liên hệ qua email trong 1-2 ngày làm việc.
            </p>
        </form>
    </div>
</div>

</body>
</html>
