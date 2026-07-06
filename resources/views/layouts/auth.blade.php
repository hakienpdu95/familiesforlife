<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" data-theme="light">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">

    <title>@yield('title', config('app.name')) — {{ config('app.name') }}</title>

    @vite(['resources/css/app.css', 'resources/js/app.js'], 'build/backend')

    @stack('styles')
</head>
<body class="bg-base-200 min-h-screen flex items-center justify-center px-4 py-8">

    @yield('content')

    @if (session('status'))
    <script>
        document.addEventListener('DOMContentLoaded', () =>
            window.Toast?.success(@js(session('status')))
        )
    </script>
    @endif

    @if (\Modules\Auth\Fortify\ValidateTurnstile::isActive())
    <script src="https://challenges.cloudflare.com/turnstile/v0/api.js" async defer></script>
    @endif

    @stack('scripts')
</body>
</html>
