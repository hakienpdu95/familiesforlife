@props(['model' => null, 'fields' => [], 'description' => null, 'note' => null, 'fallbacks' => []])
@foreach(\App\Support\I18n\PortalLocale::prefixed() as $locale)
@php
    $translation = $model?->translationFor($locale);
    $languageLabel = \App\Support\I18n\PortalLocale::label($locale);
    $hasErrors = $errors->has("translations.{$locale}.*");
    $values = [];
    foreach ($fields as $key => $field) {
        $values[$key] = old("translations.{$locale}.{$key}", $translation?->{$key});
    }
    $total = count($fields);
    $filled = collect($values)->filter(fn ($v) => filled(is_string($v) ? trim(strip_tags($v)) : $v))->count();
    $compact = $total === 1 && in_array(collect($fields)->first()['type'] ?? 'text', ['text', 'textarea'], true);
    $localeFallbacks = collect($fallbacks)->map(fn ($byLocale) => $byLocale[$locale] ?? null)->all();
    $inherited = collect($localeFallbacks)->filter()->count();
    $noteText = $note ?? __('Ô để trống dùng giá trị bản gốc. Để trống toàn bộ = chưa có bản dịch: trang /:locale/ hiển thị bản gốc kèm thông báo và noindex.', ['locale' => $locale]);
@endphp
<details class="group rounded-box border bg-base-100 overflow-hidden {{ $hasErrors ? 'border-error/60' : 'border-base-200' }}"
         id="translation-{{ $locale }}" data-translation-block @if($translation || $hasErrors || $inherited) open @endif
         x-init="if (location.hash === '#translation-{{ $locale }}') { $el.open = true; $nextTick(() => $el.scrollIntoView({ block: 'center' })) }"
         x-data="translationBlock({{ $filled }}, {{ $total }}, @js($locale), @js(collect($localeFallbacks)->map(fn ($f) => (bool) $f)->all()))"
         @input="recount()" @change="recount()" @translation-sync.window="recount()">
    <summary class="flex items-center gap-3 px-4 py-3 cursor-pointer select-none list-none [&::-webkit-details-marker]:hidden hover:bg-base-200/40 transition-colors">
        <span class="inline-flex items-center justify-center w-8 h-8 rounded-lg bg-primary/10 text-primary text-[11px] font-bold uppercase tracking-wide shrink-0">{{ $locale }}</span>
        <span class="min-w-0 flex-1">
            <span class="block text-sm font-semibold text-base-content">{{ __('Bản dịch :language', ['language' => $languageLabel]) }}</span>
            @if($compact)
            <span class="block text-xs text-base-content/50 truncate">{{ $description ?? __('Hiển thị tại /:locale/', ['locale' => $locale]) }}</span>
            @else
            <span class="block text-xs text-base-content/50" x-text="@js(__(':filled/:total trường đã dịch', ['total' => $total])).replace(':filled', filled)">{{ __(':filled/:total trường đã dịch', ['filled' => $filled, 'total' => $total]) }}</span>
            @endif
        </span>
        @unless($compact)
        <span class="hidden sm:block w-20 h-1.5 rounded-full bg-base-200 overflow-hidden" aria-hidden="true">
            <span class="block h-full rounded-full transition-all duration-300"
                  :class="filled === total ? 'bg-success' : (filled > 0 ? 'bg-warning' : 'bg-base-300')"
                  :style="`width: ${total ? Math.round(filled / total * 100) : 0}%`"
                  style="width: {{ $total ? round($filled / $total * 100) : 0 }}%"></span>
        </span>
        @endunless
        <span @class(['badge badge-sm shrink-0', 'badge-success' => $filled === $total && $total > 0, 'badge-warning' => $filled > 0 && $filled < $total, 'badge-info badge-soft' => $filled === 0 && $inherited, 'badge-ghost' => $filled === 0 && ! $inherited])
              :class="filled === total ? 'badge-success' : (filled > 0 ? 'badge-warning' : (inherited ? 'badge-info badge-soft' : 'badge-ghost'))"
              x-text="filled === total ? @js($compact ? __('Đã dịch') : __('Đầy đủ')) : (filled > 0 ? @js(__('Một phần')) : (inherited ? @js(__('Tự động')) : @js(__('Chưa có'))))">{{ $filled === $total && $total > 0 ? ($compact ? __('Đã dịch') : __('Đầy đủ')) : ($filled > 0 ? __('Một phần') : ($inherited ? __('Tự động') : __('Chưa có'))) }}</span>
        <svg class="w-4 h-4 text-base-content/40 shrink-0 transition-transform group-open:rotate-180" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/></svg>
    </summary>

    <div class="border-t border-base-200">
        @unless($compact)
        <div class="flex items-start gap-2 px-4 py-2.5 bg-info/5 text-xs text-base-content/60">
            <svg class="w-4 h-4 text-info shrink-0 mt-px" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
            <p class="leading-relaxed">{{ $noteText }}</p>
        </div>
        @endunless

        <div @class(['space-y-4', 'p-4' => ! $compact, 'px-4 pt-3 pb-4' => $compact])>
            @foreach($fields as $key => $field)
            @php
                $name = "translations[{$locale}][{$key}]";
                $type = $field['type'] ?? 'text';
                $value = $values[$key];
                $source = $model?->originalAttribute($key);
                $maxlength = $field['maxlength'] ?? null;
                $inputId = "tr-{$locale}-{$key}-".($model?->getKey() ?? 'new');
            @endphp
            @php($fallback = $localeFallbacks[$key] ?? null)
            <div x-data="translationField(@js($key), @js(is_string($source) ? $source : ''), @js($type), @js($locale), @js($fallback))">
                <div class="flex items-center justify-between gap-2 mb-1.5">
                    <label for="{{ $inputId }}" class="text-sm font-medium text-base-content">
                        {{ $field['label'] }}
                        @if(! empty($field['hint']))<span class="ml-1 text-xs font-normal text-base-content/40">{{ $field['hint'] }}</span>@endif
                    </label>
                    <div class="flex items-center gap-2 shrink-0">
                        @if($maxlength && $type === 'text')
                        <span class="text-[11px] tabular-nums text-base-content/40" x-text="`${length}/{{ $maxlength }}`">{{ mb_strlen((string) $value) }}/{{ $maxlength }}</span>
                        @endif
                        @unless($compact && $type === 'text')
                        <button type="button" class="btn btn-ghost btn-xs gap-1 text-base-content/60" x-show="source" @click="copySource()" title="{{ __('Chép bản gốc vào ô dịch') }}">
                            <svg class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 16H6a2 2 0 01-2-2V6a2 2 0 012-2h8a2 2 0 012 2v2m-6 12h8a2 2 0 002-2v-8a2 2 0 00-2-2h-8a2 2 0 00-2 2v8a2 2 0 002 2z"/></svg>
                            {{ __('Chép bản gốc') }}
                        </button>
                        @endunless
                    </div>
                </div>

                @if($compact && $type === 'text')
                <div class="join w-full">
                    <input id="{{ $inputId }}" type="text" name="{{ $name }}" value="{{ $value }}" lang="{{ $locale }}" data-tr-field x-ref="input"
                           @input="length = $event.target.value.length"
                           :placeholder="fallback?.value || source" placeholder="{{ $fallback['value'] ?? (is_string($source) ? $source : '') }}"
                           class="input input-bordered input-sm join-item w-full @error("translations.{$locale}.{$key}") input-error @enderror"
                           @if($maxlength) maxlength="{{ $maxlength }}" @endif>
                    <button type="button" class="btn btn-sm join-item border-base-300 bg-base-200/60 font-normal text-base-content/70 gap-1" x-show="source" @click="copySource()" title="{{ __('Chép bản gốc vào ô dịch') }}">
                        <svg class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 16H6a2 2 0 01-2-2V6a2 2 0 012-2h8a2 2 0 012 2v2m-6 12h8a2 2 0 002-2v-8a2 2 0 00-2-2h-8a2 2 0 00-2 2v8a2 2 0 002 2z"/></svg>
                        <span class="hidden sm:inline">{{ __('Chép bản gốc') }}</span>
                    </button>
                </div>
                @elseif($type === 'textarea')
                <textarea id="{{ $inputId }}" name="{{ $name }}" rows="{{ $field['rows'] ?? 3 }}" lang="{{ $locale }}" data-tr-field x-ref="input" @input="length = $event.target.value.length"
                          :placeholder="fallback?.value || source" placeholder="{{ $fallback['value'] ?? (is_string($source) ? $source : '') }}"
                          class="textarea textarea-bordered textarea-sm w-full leading-relaxed @error("translations.{$locale}.{$key}") textarea-error @enderror">{{ $value }}</textarea>
                @elseif($type === 'editor')
                <textarea id="{{ $inputId }}" name="{{ $name }}" lang="{{ $locale }}" data-tr-field x-ref="input" class="jodit-editor" data-translation-editor data-jodit-preset="{{ $field['preset'] ?? 'full' }}" data-jodit-context-type="{{ $field['context'] ?? '' }}">{{ $value }}</textarea>
                @else
                <input id="{{ $inputId }}" type="text" name="{{ $name }}" value="{{ $value }}" lang="{{ $locale }}" data-tr-field x-ref="input"
                       @input="length = $event.target.value.length"
                       :placeholder="fallback?.value || source" placeholder="{{ $fallback['value'] ?? (is_string($source) ? $source : '') }}"
                       class="input input-bordered input-sm w-full @error("translations.{$locale}.{$key}") input-error @enderror"
                       @if($maxlength) maxlength="{{ $maxlength }}" @endif>
                @endif

                @if($compact)
                <div class="mt-2 flex flex-wrap items-center gap-x-2 gap-y-1 rounded-lg bg-info/5 px-3 py-2 text-xs text-base-content/70"
                     x-show="fallback && ! length" @if(! $fallback || filled($value)) style="display:none" @endif>
                    <svg class="w-3.5 h-3.5 text-info shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13.828 10.172a4 4 0 00-5.656 0l-4 4a4 4 0 105.656 5.656l1.102-1.101m-.758-4.899a4 4 0 005.656 0l4-4a4 4 0 00-5.656-5.656l-1.1 1.1"/></svg>
                    <span class="min-w-0" x-html="inheritedText(@js(__('Đang tự dùng :value từ :from')))">{!! $fallback ? __('Đang tự dùng :value từ :from', ['value' => '<strong class="font-semibold text-base-content">'.e($fallback['value']).'</strong>', 'from' => e($fallback['from'])]) : '' !!}</span>
                    <button type="button" class="link link-primary link-hover ml-auto" @click="useFallback()">{{ __('Dùng làm nhãn riêng') }}</button>
                </div>
                <p class="mt-1.5 text-xs text-base-content/45 leading-relaxed">{{ $noteText }}</p>
                @elseif($type !== 'editor')
                <p class="mt-1 text-xs text-base-content/40 truncate" x-show="source && length > 0" @if(! filled($source) || ! filled($value)) style="display:none" @endif>
                    <span class="font-medium text-base-content/50">{{ __('Bản gốc') }}:</span>
                    <span x-text="source">{{ is_string($source) ? $source : '' }}</span>
                </p>
                @endif
                @error("translations.{$locale}.{$key}")<p class="mt-1 text-xs text-error">{{ $message }}</p>@enderror
            </div>
            @endforeach
        </div>

        <div class="flex items-center justify-between gap-2 px-4 py-2 border-t border-base-200 bg-base-200/30" x-show="filled > 0 || @js((bool) $translation)" @if($filled === 0 && ! $translation) style="display:none" @endif>
            <span class="text-xs text-base-content/40">
                @if($translation?->updated_at)
                {{ __('Cập nhật lần cuối :time', ['time' => format_datetime($translation->updated_at)]) }}
                @else
                {{ __('Chưa lưu') }}
                @endif
            </span>
            <button type="button" class="btn btn-ghost btn-xs text-error" x-show="filled > 0" @if($filled === 0) style="display:none" @endif
                    @click="clearAll(@js(__('Xoá toàn bộ nội dung bản dịch :language? Bản dịch chỉ bị xoá khi bạn lưu lại.', ['language' => $languageLabel])))">
                {{ __('Xoá bản dịch') }}
            </button>
        </div>
    </div>
</details>
@endforeach
@once
@push('scripts')
<script>
(() => {
    const escapeHtml = (value) => String(value ?? '').replace(/&/g, '&amp;').replace(/</g, '&lt;').replace(/>/g, '&gt;').replace(/"/g, '&quot;');
    const plain = (value) => String(value ?? '').replace(/<[^>]*>/g, '').replace(/&nbsp;/g, ' ').trim();
    const editorFor = (el) => Object.values(window.Jodit?.instances || {}).find((editor) => editor.element === el);
    const writeValue = (el, value) => {
        const editor = editorFor(el);
        if (editor) {
            editor.value = value;
        } else {
            el.value = value;
        }
        el.dispatchEvent(new Event('input', { bubbles: true }));
        window.dispatchEvent(new CustomEvent('translation-sync'));
    };

    document.addEventListener('alpine:init', () => {
        window.Alpine.data('translationBlock', (filled, total, locale, fallbackKeys = {}) => ({
            filled,
            total,
            fallbackKeys: { ...fallbackKeys },
            get inherited() {
                return Object.values(this.fallbackKeys).some(Boolean);
            },
            init() {
                window.addEventListener('translation-fallback', (e) => {
                    if (e.detail.locale === locale) {
                        this.fallbackKeys[e.detail.key] = !! e.detail.value;
                    }
                });
            },
            recount() {
                this.$nextTick(() => {
                    this.filled = [...this.$root.querySelectorAll('[data-tr-field]')].filter((el) => plain(editorFor(el)?.value ?? el.value) !== '').length;
                });
            },
            clearAll(message) {
                if (! window.confirm(message)) {
                    return;
                }
                this.$root.querySelectorAll('[data-tr-field]').forEach((el) => writeValue(el, ''));
                this.recount();
            },
        }));

        window.Alpine.data('translationField', (key, source, type, locale, fallback = null) => ({
            source,
            fallback,
            length: 0,
            init() {
                this.length = this.$refs.input?.value.length ?? 0;
                window.addEventListener('translation-fallback', (e) => {
                    if (e.detail.key === key && e.detail.locale === locale) {
                        this.fallback = e.detail.value ? { value: e.detail.value, from: e.detail.from } : null;
                    }
                });
                const origin = this.$root.closest('form')?.querySelector(`[name="${key}"]`);
                if (! origin) {
                    return;
                }
                const read = () => {
                    const value = editorFor(origin)?.value ?? origin.value;
                    this.source = type === 'editor' ? value : plain(value);
                };
                read();
                origin.addEventListener('input', read);
                origin.addEventListener('change', read);
            },
            inheritedText(template) {
                if (! this.fallback) {
                    return '';
                }
                return escapeHtml(template)
                    .replace(':value', '<strong class="font-semibold text-base-content">' + escapeHtml(this.fallback.value) + '</strong>')
                    .replace(':from', escapeHtml(this.fallback.from));
            },
            useFallback() {
                writeValue(this.$refs.input, this.fallback?.value ?? '');
                this.length = this.$refs.input.value.length;
                this.$refs.input.focus();
            },
            copySource() {
                writeValue(this.$refs.input, this.source);
                this.length = this.$refs.input.value.length;
                this.$refs.input.focus();
            },
        }));
    });

    document.addEventListener('DOMContentLoaded', () => {
        if (! window.initJodit || ! window.Jodit) {
            return;
        }
        document.querySelectorAll('textarea[data-translation-editor]').forEach((el) => {
            if (! editorFor(el)) {
                window.initJodit(el);
            }
            const editor = editorFor(el);
            editor?.events?.on('change', () => el.dispatchEvent(new Event('change', { bubbles: true })));
        });
    });
})();
</script>
@endpush
@endonce
