<!DOCTYPE html>
<html lang="vi" data-theme="portal">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="robots" content="noindex">
    <title>{{ $title }} — {{ $heading }} — {{ $brand }}</title>
    @include('layouts.partials.font-preload', ['fonts' => ['roboto'], 'build' => 'build/frontend'])
    @vite(['resources/css/icon.css', 'resources/css/frontend.css'], 'build/frontend')
</head>
<body class="bg-base-200 text-base-content antialiased">
    <main class="max-w-md mx-auto min-h-screen bg-base-100 pb-28">
        @if ($archived)
            <div role="alert" class="flex items-start gap-2 bg-orange-100 border-b-2 border-orange-400 text-orange-900 text-sm font-medium px-4 py-3">
                <i class="fa-solid fa-triangle-exclamation mt-0.5" aria-hidden="true"></i>
                <div>
                    <p>{{ $archive_text }}</p>
                    @if ($archived_at)
                        <p class="text-xs font-normal text-orange-800/80 mt-0.5">Bản lưu trữ cập nhật lần cuối ngày {{ $archived_at }}.</p>
                    @endif
                </div>
            </div>
        @endif

        <header class="flex items-center justify-between gap-3 px-4 py-3 border-b border-base-200">
            <p class="flex items-center gap-2 text-xs font-semibold uppercase tracking-wide text-primary">
                <i class="fa-solid {{ $icon }}" aria-hidden="true"></i>
                {{ $heading }}
            </p>
            <span class="text-xs text-base-content/50 truncate">{{ $brand }}</span>
        </header>

        @if ($revoked || $notice)
            <div class="px-4 pt-4 space-y-2">
                @if ($revoked)
                    <div role="alert" class="rounded-lg border border-red-300 bg-red-50 text-red-800 text-sm px-3 py-2.5">
                        <i class="fa-solid fa-ban mr-1" aria-hidden="true"></i>
                        Mã QR này đã bị thu hồi. Thông tin bên dưới chỉ mang tính tham khảo lịch sử.
                    </div>
                @endif


                @if ($notice)
                    <div role="status" class="rounded-lg border border-amber-300 bg-amber-50 text-amber-800 text-sm px-3 py-2.5">
                        <i class="fa-solid fa-circle-info mr-1" aria-hidden="true"></i>
                        {{ $notice }}
                    </div>
                @endif
            </div>
        @endif

        @yield('content')

        <p class="px-4 mt-8 text-center text-xs text-base-content/40">
            Mã truy xuất <span class="font-mono font-semibold text-base-content/60">{{ $qr_code }}</span>
        </p>
    </main>

    <div class="fixed bottom-0 inset-x-0 z-10 bg-base-100 border-t border-base-300 px-4 pt-3 pb-[max(0.75rem,env(safe-area-inset-bottom))]">
        <a href="{{ $cta['url'] }}" class="btn btn-primary w-full max-w-md mx-auto flex">
            {{ $cta['label'] }}
        </a>
    </div>
</body>
</html>
