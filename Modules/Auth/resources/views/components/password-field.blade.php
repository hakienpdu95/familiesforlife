@props(['name', 'label', 'required' => true])

<fieldset class="fieldset p-0" x-data="{ show: false }">
    <label for="{{ $name }}" class="fieldset-legend pt-0 text-sm font-medium">
        {{ $label }} @if ($required)<span class="text-error">*</span>@endif
    </label>
    <label @class(['input input-lg w-full focus-within:input-primary', 'input-error' => $errors->has($name)])>
        <input
            id="{{ $name }}"
            :type="show ? 'text' : 'password'"
            type="password"
            name="{{ $name }}"
            @required($required)
            {{ $attributes->class('grow text-base') }}
        />
        <button
            type="button"
            @click="show = !show"
            tabindex="-1"
            class="btn btn-ghost btn-sm btn-square text-base-content/50"
            :aria-label="show ? 'Ẩn mật khẩu' : 'Hiện mật khẩu'"
        >
            <svg x-show="!show" class="size-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/>
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"/>
            </svg>
            <svg x-show="show" x-cloak class="size-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13.875 18.825A10.05 10.05 0 0112 19c-4.478 0-8.268-2.943-9.543-7a9.97 9.97 0 011.563-3.029m5.858.908a3 3 0 114.243 4.243M9.878 9.878l4.242 4.242M9.88 9.88l-3.29-3.29m7.532 7.532l3.29 3.29M3 3l3.59 3.59m0 0A9.953 9.953 0 0112 5c4.478 0 8.268 2.943 9.543 7a10.025 10.025 0 01-4.132 4.411m0 0L21 21"/>
            </svg>
        </button>
    </label>
</fieldset>
