<!DOCTYPE html>
<html lang="vi" data-theme="light">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Đăng ký thành công — Marketplace</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'], 'build/backend')
</head>
<body class="bg-base-200 min-h-screen flex items-center justify-center p-4">

<div class="card bg-base-100 shadow-xl w-full max-w-md text-center">
    <div class="card-body py-12">
        <div class="inline-flex items-center justify-center w-16 h-16 bg-success/10 rounded-full mb-4 mx-auto">
            <svg class="w-8 h-8 text-success" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/>
            </svg>
        </div>

        <h1 class="text-2xl font-bold">Đăng ký thành công!</h1>
        <p class="text-sm opacity-60 mt-2 px-4">
            Hồ sơ doanh nghiệp của bạn đã được gửi đến đội ngũ của chúng tôi.
            Chúng tôi sẽ xem xét và thông báo qua email trong <strong>1-2 ngày làm việc</strong>.
        </p>

        @if(session('success'))
        <div class="alert alert-success mt-4 text-sm">
            {{ session('success') }}
        </div>
        @endif

        <div class="mt-6 p-4 bg-base-200 rounded-xl text-sm text-left space-y-2">
            <p class="font-semibold opacity-70">Các bước tiếp theo:</p>
            <div class="flex items-start gap-2">
                <span class="badge badge-success badge-xs mt-1">✓</span>
                <span>Hồ sơ đã được ghi nhận</span>
            </div>
            <div class="flex items-start gap-2">
                <span class="badge badge-ghost badge-xs mt-1">2</span>
                <span>Admin xem xét và duyệt tổ chức</span>
            </div>
            <div class="flex items-start gap-2">
                <span class="badge badge-ghost badge-xs mt-1">3</span>
                <span>Nhận email xác nhận và đăng nhập</span>
            </div>
            <div class="flex items-start gap-2">
                <span class="badge badge-ghost badge-xs mt-1">4</span>
                <span>Tin đăng của bạn sẽ hiển thị công khai</span>
            </div>
        </div>

        <a href="{{ route('marketplace.employer.register') }}" class="btn btn-ghost btn-sm mt-4">
            Đăng ký tổ chức khác
        </a>
    </div>
</div>

</body>
</html>
