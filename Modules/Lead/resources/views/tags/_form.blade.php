{{-- Reusable tag form partial --}}
@php
$colors = [
    '#6b7280' => 'Xám',
    '#ef4444' => 'Đỏ',
    '#f97316' => 'Cam',
    '#f59e0b' => 'Vàng',
    '#10b981' => 'Xanh lá',
    '#06b6d4' => 'Xanh lam nhạt',
    '#3b82f6' => 'Xanh dương',
    '#8b5cf6' => 'Tím',
    '#ec4899' => 'Hồng',
];
$currentColor = old('color', $tag->color ?? '#6b7280');
@endphp

<div class="form-control mb-4">
    <label class="label py-0 pb-1.5">
        <span class="label-text font-medium">Tên tag <span class="text-error">*</span></span>
    </label>
    <input type="text" name="name"
           value="{{ old('name', $tag->name ?? '') }}"
           required maxlength="50"
           class="input input-bordered input-sm @error('name') input-error @enderror"
           placeholder="VD: Quan trọng, Hot, Tặng quà...">
    @error('name')<p class="mt-1 text-xs text-error">{{ $message }}</p>@enderror
</div>

<div class="form-control mb-4">
    <label class="label py-0 pb-1.5">
        <span class="label-text font-medium">Màu sắc</span>
    </label>
    <div class="flex flex-wrap gap-2" x-data="{ selected: '{{ $currentColor }}' }">
        @foreach($colors as $hex => $label)
        <label class="cursor-pointer" title="{{ $label }}">
            <input type="radio" name="color" value="{{ $hex }}" class="hidden"
                   {{ $currentColor === $hex ? 'checked' : '' }}
                   x-on:change="selected = '{{ $hex }}'">
            <span class="block w-7 h-7 rounded-full border-2 transition-all"
                  style="background: {{ $hex }}"
                  :class="selected === '{{ $hex }}' ? 'border-base-content scale-110' : 'border-transparent'">
            </span>
        </label>
        @endforeach

        {{-- Preview --}}
        <div class="flex items-center ml-2">
            <span class="badge badge-sm text-white text-xs font-medium"
                  x-bind:style="'background:' + selected"
                  x-text="$el.closest('form').querySelector('[name=name]')?.value || 'Preview'">
            </span>
        </div>
    </div>
    <input type="hidden" name="color" id="colorHidden" value="{{ $currentColor }}">
    @error('color')<p class="mt-1 text-xs text-error">{{ $message }}</p>@enderror
</div>
