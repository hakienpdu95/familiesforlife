<!DOCTYPE html>
<html lang="vi" data-theme="anland">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>@hasSection('title')@yield('title') — @endif Anland</title>
    @hasSection('meta_description')
    <meta name="description" content="@yield('meta_description')">
    @endif

    @vite(['resources/css/anland.css', 'resources/js/anland.js'], 'build/frontend')
    @stack('styles')
</head>
<body class="bg-base-100 text-base-content">

<div class="flex flex-col min-h-screen">
    @include('realestate::public.anland.partials.header')

    <main class="flex-1">
        @yield('content')
    </main>

    @include('realestate::public.anland.partials.footer')
</div>

@stack('scripts')
</body>
</html>
