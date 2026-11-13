@if($gaMeasurementId = \App\Support\Analytics\GoogleAnalyticsTag::measurementIdFor(auth()->user()))
<script async src="https://www.googletagmanager.com/gtag/js?id={{ $gaMeasurementId }}"></script>
<script>
    window.dataLayer = window.dataLayer || [];
    function gtag(){dataLayer.push(arguments);}
    gtag('js', new Date());
    gtag('config', @js($gaMeasurementId));
</script>
@if(\App\Support\Analytics\ClickTracking::scriptBuilt())
<script>window.__clickTracking = @js(\App\Support\Analytics\ClickTracking::clientConfig());</script>
@vite([\App\Support\Analytics\ClickTracking::SCRIPT], \App\Support\Analytics\ClickTracking::BUILD_DIRECTORY)
@endif
@endif
