@foreach ($fonts as $font)
    @foreach (['latin', 'vietnamese'] as $subset)
        <link rel="preload" as="font" type="font/woff2" crossorigin href="{{ Vite::asset("node_modules/@fontsource-variable/{$font}/files/{$font}-{$subset}-wght-normal.woff2", $build) }}">
    @endforeach
@endforeach
