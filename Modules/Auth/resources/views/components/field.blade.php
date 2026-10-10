@props(['name', 'label', 'type' => 'text', 'value' => null, 'required' => true])

<fieldset class="fieldset p-0">
    <label for="{{ $name }}" class="fieldset-legend pt-0 text-sm font-medium">
        {{ $label }} @if ($required)<span class="text-error">*</span>@endif
    </label>
    <input
        id="{{ $name }}"
        type="{{ $type }}"
        name="{{ $name }}"
        value="{{ old($name, $value) }}"
        @required($required)
        {{ $attributes->class(['input input-lg w-full text-base focus:input-primary', 'input-error' => $errors->has($name)]) }}
    />
</fieldset>
