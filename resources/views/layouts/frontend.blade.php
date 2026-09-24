<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', $locale ?? app()->getLocale()) }}" data-theme="portal">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>@hasSection('title')@yield('title') — @endif{{ config('app.site_name') }}</title>
    @hasSection('meta_description')
    <meta name="description" content="@yield('meta_description')">
    @endif
    @stack('meta')

    @php
        $homeUrl = route('post.public.home');

        $organizationNode = array_filter([
            '@type'       => 'Organization',
            '@id'         => $homeUrl . '#organization',
            'name'        => config('app.site_name'),
            'url'         => $homeUrl,
            'description' => config('app.site_description') ?: null,
            'logo'        => config('app.site_logo_url') ?: null,
            'sameAs'      => config('app.site_social_links') ?: null,
        ]);

        $websiteNode = [
            '@type'     => 'WebSite',
            '@id'       => $homeUrl . '#website',
            'name'      => config('app.site_name'),
            'url'       => $homeUrl,
            'publisher' => ['@id' => $homeUrl . '#organization'],
            'potentialAction' => [
                '@type'  => 'SearchAction',
                'target' => [
                    '@type'       => 'EntryPoint',
                    'urlTemplate' => $homeUrl . '?q={search_term_string}',
                ],
                'query-input' => 'required name=search_term_string',
            ],
        ];

        $graph = [$organizationNode, $websiteNode];

        if (($menuTree ?? collect())->isNotEmpty()) {
            $graph = [...$graph, ...$menuTree->map(fn ($item) => [
                '@type' => 'SiteNavigationElement',
                'name'  => $item->label,
                'url'   => $item->resolveUrl() ?? url('/'),
            ])->values()->all()];
        }

        $siteJsonLd = json_encode([
            '@context' => 'https://schema.org',
            '@graph'   => $graph,
        ], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
    @endphp
    <script type="application/ld+json">{!! $siteJsonLd !!}</script>

    <style>
        html { visibility: hidden; animation: ffl-reveal 0s 3s forwards; }
        @keyframes ffl-reveal { to { visibility: visible; } }
        html.is-loading *, html.is-loading *::before, html.is-loading *::after { transition: none !important; }
    </style>
    <script>
        document.documentElement.classList.add('is-loading');
        window.addEventListener('load', () => requestAnimationFrame(() => document.documentElement.classList.remove('is-loading')));
    </script>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Merriweather:ital,wght@0,300;0,400;0,700;0,900;1,300;1,400;1,700;1,900&family=Roboto:ital,wght@0,300;0,400;0,500;0,700;0,900;1,300;1,400;1,500;1,700;1,900&display=swap" rel="stylesheet">
    @vite(['resources/css/frontend.css', 'resources/js/frontend.js'], 'build/frontend')
    @stack('styles')
</head>
<body class="text-base-content">

<div x-data="frontendNav" class="flex flex-col min-h-screen">
    @include('layouts.partials.frontend-header')

    <main class="site-content flex-1">
        @yield('content')
    </main>

    @include('layouts.partials.frontend-footer')
</div>

@stack('scripts')
</body>
</html>
