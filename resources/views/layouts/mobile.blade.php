<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" data-theme="light">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <meta name="theme-color" content="#3b82f6">
    @auth<meta name="user-id" content="{{ auth()->id() }}">@endauth
    <title>@yield('title', 'Khảo sát') — {{ config('app.name') }}</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'], 'build/backend')
    <style>
        body { min-height: 100dvh; }
        /* Touch targets ≥44px */
        button, a, input[type=checkbox], input[type=radio] { min-height: 44px; }
        input[type=text], input[type=number], input[type=email], textarea, select {
            font-size: 16px; /* prevent iOS auto-zoom */
        }
    </style>
    @stack('styles')
</head>
<body class="bg-base-200">

{{-- Mobile top bar --}}
<header class="bg-base-100 border-b border-base-200 sticky top-0 z-40 shadow-sm">
    <div class="flex items-center gap-3 px-4 h-14">
        @hasSection('back_url')
        <a href="@yield('back_url')" class="btn btn-ghost btn-circle btn-sm -ml-2">
            <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"/>
            </svg>
        </a>
        @endif
        <div class="flex-1 min-w-0">
            <p class="font-semibold text-sm truncate">@yield('title', 'Khảo sát')</p>
            @hasSection('subtitle')
            <p class="text-xs text-base-content/50 truncate">@yield('subtitle')</p>
            @endif
        </div>
        @hasSection('header_action')
        @yield('header_action')
        @endif
    </div>
</header>

<main class="px-4 py-5 pb-24 max-w-lg mx-auto">
    @yield('content')
</main>

{{-- Bottom nav placeholder for future tabs --}}
@hasSection('bottom_nav')
<nav class="fixed bottom-0 inset-x-0 bg-base-100 border-t border-base-200 h-16 flex items-center justify-around z-40 safe-area-inset-bottom">
    @yield('bottom_nav')
</nav>
@endif

@stack('scripts')
</body>
</html>
