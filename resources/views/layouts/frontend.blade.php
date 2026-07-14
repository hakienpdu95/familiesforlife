<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', $locale ?? app()->getLocale()) }}" data-theme="portal">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>@hasSection('title')@yield('title') — @endif{{ config('app.name', 'Cổng thông tin') }}</title>
    @hasSection('meta_description')
    <meta name="description" content="@yield('meta_description')">
    @endif
    @stack('meta')

    @vite(['resources/css/frontend.css', 'resources/js/frontend.js'], 'build/frontend')
    @stack('styles')
</head>
<body class="bg-base-100 text-base-content">

<div class="drawer" x-data="frontendNav">
    <input id="portal-drawer" type="checkbox" class="drawer-toggle" />

    <div class="drawer-content flex flex-col min-h-screen">
        @include('layouts.partials.frontend-topbar')
        @include('layouts.partials.frontend-header')
        @include('layouts.partials.frontend-nav')

        <main class="flex-1">
            @yield('content')
        </main>

        @include('layouts.partials.frontend-footer')
    </div>

    @include('layouts.partials.frontend-drawer')
</div>

@stack('scripts')
</body>
</html>
