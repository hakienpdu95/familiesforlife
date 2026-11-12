@props(['model' => null, 'fields' => []])
@foreach(\App\Support\I18n\PortalLocale::prefixed() as $locale)
@php($translation = $model?->translationFor($locale))
<details class="collapse collapse-arrow bg-base-100 border border-base-200 rounded-box" @if($translation || $errors->has("translations.{$locale}.*")) open @endif>
    <summary class="collapse-title text-sm font-semibold flex items-center gap-2">
        {{ __('Bản dịch :language', ['language' => \App\Support\I18n\PortalLocale::label($locale)]) }}
        <span @class(['badge badge-sm', 'badge-success' => $translation, 'badge-ghost' => ! $translation])>{{ $translation ? __('Đã có') : __('Chưa có') }}</span>
    </summary>
    <div class="collapse-content space-y-3">
        <p class="text-xs text-base-content/50">{{ __('Để trống toàn bộ = chưa có bản dịch: trang /:locale/ sẽ hiển thị bản gốc kèm thông báo và noindex. Ô nào để trống sẽ dùng giá trị bản gốc.', ['locale' => $locale]) }}</p>
        @foreach($fields as $key => $field)
        @php($name = "translations[{$locale}][{$key}]")
        @php($value = old("translations.{$locale}.{$key}", $translation?->{$key}))
        <div class="form-control">
            <label class="label py-0 pb-1.5">
                <span class="label-text font-medium">{{ $field['label'] }}</span>
                @if(! empty($field['hint']))<span class="label-text-alt text-xs text-base-content/40">{{ $field['hint'] }}</span>@endif
            </label>
            @if(($field['type'] ?? 'text') === 'textarea')
            <textarea name="{{ $name }}" rows="{{ $field['rows'] ?? 3 }}" lang="{{ $locale }}" class="textarea textarea-bordered textarea-sm w-full">{{ $value }}</textarea>
            @elseif(($field['type'] ?? 'text') === 'editor')
            <textarea name="{{ $name }}" lang="{{ $locale }}" class="jodit-editor" data-translation-editor data-jodit-preset="{{ $field['preset'] ?? 'full' }}" data-jodit-context-type="{{ $field['context'] ?? '' }}">{{ $value }}</textarea>
            @else
            <input type="text" name="{{ $name }}" value="{{ $value }}" lang="{{ $locale }}" class="input input-bordered input-sm w-full" @if(! empty($field['maxlength'])) maxlength="{{ $field['maxlength'] }}" @endif>
            @endif
            @error("translations.{$locale}.{$key}")<p class="text-xs text-error mt-1">{{ $message }}</p>@enderror
        </div>
        @endforeach
    </div>
</details>
@endforeach
@once
@push('scripts')
<script>
document.addEventListener('DOMContentLoaded', () => {
    if (! window.initJodit || ! window.Jodit) {
        return;
    }
    const initialized = (el) => Object.values(window.Jodit.instances || {}).some((editor) => editor.element === el);
    document.querySelectorAll('textarea[data-translation-editor]').forEach((el) => {
        if (! initialized(el)) {
            window.initJodit(el);
        }
    });
});
</script>
@endpush
@endonce
