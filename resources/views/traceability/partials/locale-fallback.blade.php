@php($portal = app(\App\Support\I18n\PortalLocale::class))
@if ($portal->showFallbackNotice())
<div class="flex items-start gap-2.5 px-4 py-2.5 bg-[#fef3c7] border-b border-[#fde68a] text-[13px] leading-snug text-[#78350f]" role="note">
    <svg class="w-4 h-4 shrink-0 mt-px" fill="none" viewBox="0 0 24 24" stroke="currentColor" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 5h12M9 3v2m1.048 9.5A18.022 18.022 0 016.412 9m6.088 9h7M11 21l5-10 5 10M12.751 5C11.783 10.77 8.07 15.61 3 18.129"/></svg>
    <p class="flex-1 min-w-0">
        {{ __('Bản dịch :language cho mục này chưa có — nội dung đang hiển thị bằng ngôn ngữ gốc.', ['language' => \App\Support\I18n\PortalLocale::label($portal->current())]) }}
        @if ($portal->originalUrl())
        <a href="{{ $portal->originalUrl() }}" hreflang="{{ \App\Support\I18n\PortalLocale::default() }}" class="font-semibold underline underline-offset-2">{{ __('Xem bản gốc') }}</a>
        @endif
    </p>
</div>
@endif
