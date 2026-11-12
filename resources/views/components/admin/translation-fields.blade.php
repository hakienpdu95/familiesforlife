@props(['model' => null, 'fields' => []])
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
@endphp
<details class="group rounded-box border bg-base-100 overflow-hidden {{ $hasErrors ? 'border-error/60' : 'border-base-200' }}"
         id="translation-{{ $locale }}" data-translation-block @if($translation || $hasErrors) open @endif
         x-init="if (location.hash === '#translation-{{ $locale }}') { $el.open = true; $nextTick(() => $el.scrollIntoView({ block: 'center' })) }"
         x-data="translationBlock({{ $filled }}, {{ $total }})"
         @input="recount()" @change="recount()" @translation-sync.window="recount()">
    <summary class="flex items-center gap-3 px-4 py-3 cursor-pointer select-none list-none [&::-webkit-details-marker]:hidden hover:bg-base-200/40 transition-colors">
        <span class="inline-flex items-center justify-center w-8 h-8 rounded-lg bg-primary/10 text-primary text-[11px] font-bold uppercase tracking-wide shrink-0">{{ $locale }}</span>
        <span class="min-w-0 flex-1">
            <span class="block text-sm font-semibold text-base-content">{{ __('Bản dịch :language', ['language' => $languageLabel]) }}</span>
            <span class="block text-xs text-base-content/50" x-text="@js(__(':filled/:total trường đã dịch', ['total' => $total])).replace(':filled', filled)">{{ __(':filled/:total trường đã dịch', ['filled' => $filled, 'total' => $total]) }}</span>
        </span>
        <span class="hidden sm:block w-20 h-1.5 rounded-full bg-base-200 overflow-hidden" aria-hidden="true">
            <span class="block h-full rounded-full transition-all duration-300"
                  :class="filled === total ? 'bg-success' : (filled > 0 ? 'bg-warning' : 'bg-base-300')"
                  :style="`width: ${total ? Math.round(filled / total * 100) : 0}%`"
                  style="width: {{ $total ? round($filled / $total * 100) : 0 }}%"></span>
        </span>
        <span @class(['badge badge-sm shrink-0', 'badge-success' => $filled === $total && $total > 0, 'badge-warning' => $filled > 0 && $filled < $total, 'badge-ghost' => $filled === 0])
              :class="filled === total ? 'badge-success' : (filled > 0 ? 'badge-warning' : 'badge-ghost')"
              x-text="filled === total ? @js(__('Đầy đủ')) : (filled > 0 ? @js(__('Một phần')) : @js(__('Chưa có')))">{{ $filled === $total && $total > 0 ? __('Đầy đủ') : ($filled > 0 ? __('Một phần') : __('Chưa có')) }}</span>
        <svg class="w-4 h-4 text-base-content/40 shrink-0 transition-transform group-open:rotate-180" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/></svg>
    </summary>

    <div class="border-t border-base-200">
        <div class="flex items-start gap-2 px-4 py-2.5 bg-info/5 text-xs text-base-content/60">
            <svg class="w-4 h-4 text-info shrink-0 mt-px" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
            <p class="leading-relaxed">{{ __('Ô để trống dùng giá trị bản gốc. Để trống toàn bộ = chưa có bản dịch: trang /:locale/ hiển thị bản gốc kèm thông báo và noindex.', ['locale' => $locale]) }}</p>
        </div>

        <div class="p-4 space-y-4">
            @foreach($fields as $key => $field)
            @php
                $name = "translations[{$locale}][{$key}]";
                $type = $field['type'] ?? 'text';
                $value = $values[$key];
                $source = $model?->originalAttribute($key);
                $maxlength = $field['maxlength'] ?? null;
                $inputId = "tr-{$locale}-{$key}-".($model?->getKey() ?? 'new');
            @endphp
            <div x-data="translationField(@js($key), @js(is_string($source) ? $source : ''), @js($type))">
                <div class="flex items-center justify-between gap-2 mb-1.5">
                    <label for="{{ $inputId }}" class="text-sm font-medium text-base-content">
                        {{ $field['label'] }}
                        @if(! empty($field['hint']))<span class="ml-1 text-xs font-normal text-base-content/40">{{ $field['hint'] }}</span>@endif
                    </label>
                    <div class="flex items-center gap-2 shrink-0">
                        @if($maxlength && $type === 'text')
                        <span class="text-[11px] tabular-nums text-base-content/40" x-text="`${length}/{{ $maxlength }}`">{{ mb_strlen((string) $value) }}/{{ $maxlength }}</span>
                        @endif
                        <button type="button" class="btn btn-ghost btn-xs gap-1 text-base-content/60" x-show="source" @click="copySource()" title="{{ __('Chép bản gốc vào ô dịch') }}">
                            <svg class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 16H6a2 2 0 01-2-2V6a2 2 0 012-2h8a2 2 0 012 2v2m-6 12h8a2 2 0 002-2v-8a2 2 0 00-2-2h-8a2 2 0 00-2 2v8a2 2 0 002 2z"/></svg>
                            {{ __('Chép bản gốc') }}
                        </button>
                    </div>
                </div>

                @if($type === 'textarea')
                <textarea id="{{ $inputId }}" name="{{ $name }}" rows="{{ $field['rows'] ?? 3 }}" lang="{{ $locale }}" data-tr-field x-ref="input"
                          :placeholder="source" placeholder="{{ is_string($source) ? $source : '' }}"
                          class="textarea textarea-bordered textarea-sm w-full leading-relaxed @error("translations.{$locale}.{$key}") textarea-error @enderror">{{ $value }}</textarea>
                @elseif($type === 'editor')
                <textarea id="{{ $inputId }}" name="{{ $name }}" lang="{{ $locale }}" data-tr-field x-ref="input" class="jodit-editor" data-translation-editor data-jodit-preset="{{ $field['preset'] ?? 'full' }}" data-jodit-context-type="{{ $field['context'] ?? '' }}">{{ $value }}</textarea>
                @else
                <input id="{{ $inputId }}" type="text" name="{{ $name }}" value="{{ $value }}" lang="{{ $locale }}" data-tr-field x-ref="input"
                       @input="length = $event.target.value.length"
                       :placeholder="source" placeholder="{{ is_string($source) ? $source : '' }}"
                       class="input input-bordered input-sm w-full @error("translations.{$locale}.{$key}") input-error @enderror"
                       @if($maxlength) maxlength="{{ $maxlength }}" @endif>
                @endif

                @if($type !== 'editor')
                <p class="mt-1 text-xs text-base-content/40 truncate" x-show="source" @if(! filled($source)) style="display:none" @endif>
                    <span class="font-medium text-base-content/50">{{ __('Bản gốc') }}:</span>
                    <span x-text="source">{{ is_string($source) ? $source : '' }}</span>
                </p>
                @endif
                @error("translations.{$locale}.{$key}")<p class="mt-1 text-xs text-error">{{ $message }}</p>@enderror
            </div>
            @endforeach
        </div>

        <div class="flex items-center justify-between gap-2 px-4 py-2.5 border-t border-base-200 bg-base-200/30">
            <span class="text-xs text-base-content/40">
                @if($translation?->updated_at)
                {{ __('Cập nhật lần cuối :time', ['time' => format_datetime($translation->updated_at)]) }}
                @else
                {{ __('Chưa lưu bản dịch nào') }}
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
        window.Alpine.data('translationBlock', (filled, total) => ({
            filled,
            total,
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

        window.Alpine.data('translationField', (key, source, type) => ({
            source,
            length: 0,
            init() {
                this.length = this.$refs.input?.value.length ?? 0;
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
