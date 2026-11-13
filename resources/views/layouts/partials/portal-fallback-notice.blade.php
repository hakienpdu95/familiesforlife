@php($portal = app(\App\Support\I18n\PortalLocale::class))
@if($portal->showFallbackNotice())
<div class="portal-fallback-notice bg-warning/15 border-b border-warning/30 text-sm" role="note">
    <div class="container py-2 flex flex-wrap items-center gap-x-3 gap-y-1">
        <span>{{ __('Bản :language của trang này chưa sẵn sàng — nội dung bên dưới đang hiển thị bằng ngôn ngữ gốc.', ['language' => \App\Support\I18n\PortalLocale::label($portal->current())]) }}</span>
        @if($portal->originalUrl())
        <a href="{{ $portal->originalUrl() }}" class="link font-medium" hreflang="{{ \App\Support\I18n\PortalLocale::default() }}">{{ __('Xem bản gốc') }}</a>
        @endif
    </div>
</div>
@endif
