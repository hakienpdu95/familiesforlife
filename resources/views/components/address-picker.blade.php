<div class="grid grid-cols-1 md:grid-cols-2 gap-4">

    <div class="form-control">
        <label class="label py-0 pb-1.5">
            <span class="label-text font-medium">
                Tỉnh / Thành phố@if($required) <span class="text-error">*</span>@endif
            </span>
        </label>
        <select id="ts-prov-{{ $instanceId }}" name="{{ $nameProvince }}"
                class="select select-bordered select-sm w-full @error($nameProvince) select-error @enderror"
                @if($required) data-req="Vui lòng chọn tỉnh / thành phố" @endif>
            <option value=""></option>
            @foreach ($provinces as $p)
            <option value="{{ $p->province_code }}"
                    data-place-type="{{ $p->place_type }}"
                    {{ $provinceValue === $p->province_code ? 'selected' : '' }}>{{ $p->name }}</option>
            @endforeach
        </select>
        @error($nameProvince)<p class="mt-1 text-xs text-error form-val-msg">{{ $message }}</p>@enderror
    </div>

    <div class="form-control">
        <label class="label py-0 pb-1.5">
            <span class="label-text font-medium">
                Phường / Xã@if($required) <span class="text-error">*</span>@endif
            </span>
        </label>
        <select id="ts-ward-{{ $instanceId }}" name="{{ $nameWard }}"
                class="select select-bordered select-sm w-full @error($nameWard) select-error @enderror"
                @if($required) data-req="Vui lòng chọn phường / xã" @endif>
            <option value=""></option>
        </select>
        @error($nameWard)<p class="mt-1 text-xs text-error form-val-msg">{{ $message }}</p>@enderror
    </div>

</div>

@once
@push('scripts')
@vite(['resources/js/modules/tom-select.js'], 'build/backend')
@endpush
@endonce

@push('scripts')
<script>
document.addEventListener('DOMContentLoaded', function () {
    window.initOrgAddress(
        'ts-prov-{{ $instanceId }}',
        'ts-ward-{{ $instanceId }}',
        @json($provinceValue ?? ''),
        @json($wardValue ?? '')
    );
});
</script>
@endpush
