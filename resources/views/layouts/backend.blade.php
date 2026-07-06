<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" data-theme="light">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    @auth<meta name="user-id" content="{{ auth()->id() }}">@endauth
    @if(config('webpush.vapid.public_key'))
    <meta name="vapid-public-key" content="{{ config('webpush.vapid.public_key') }}">
    @endif
    <title>@yield('title', 'Dashboard') — {{ config('app.name', 'AdminPanel') }}</title>

    @vite(['resources/css/app.css', 'resources/js/app.js'], 'build/backend')

    @stack('styles')
</head>
<body style="background:#f1f5f9;margin:0;">

<div class="sidebar-overlay" id="sidebarOverlay"></div>

<div class="admin-shell">
    @include('layouts.partials.sidebar')
    <div class="main-area" id="mainArea">
        @include('layouts.partials.header')
        <main class="page-content">
            @section('breadcrumb')
                <x-breadcrumb />
            @show
            @yield('content')
        </main>
        @include('layouts.partials.footer')
    </div>
</div>

@if(session('success'))
<script>document.addEventListener('DOMContentLoaded',()=>window.Toast?.success(@js(session('success'))))</script>
@endif
@if(session('error'))
<script>document.addEventListener('DOMContentLoaded',()=>window.Toast?.error(@js(session('error'))))</script>
@endif

@stack('scripts')
</body>
</html>
