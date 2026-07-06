@extends('layouts.backend')
@section('title', 'Export dữ liệu — ' . $orgName)

@section('content')
<div class="max-w-2xl" x-data="fieldPicker()">

    {{-- Header --}}
    <div class="mb-6">
        <div class="text-sm text-base-content/50 mb-1">
            <a href="{{ route('deployment.targets.show', ['vertical' => $vertical->code(), 'target' => $target->id]) }}"
               class="hover:underline">← Quay lại</a>
        </div>
        <h1 class="text-xl font-bold">📊 Export dữ liệu</h1>
        <p class="text-sm text-base-content/50 mt-0.5">
            Tổ chức: <strong>{{ $orgName }}</strong>
            · Chọn các trường muốn xuất ra Excel
        </p>
    </div>

    @if(empty($catalog))
        <div class="alert alert-warning text-sm">
            Target này chưa có dữ liệu khảo sát. Vui lòng hoàn thành
            <a href="{{ route('deployment.data-collection.show', ['vertical' => $vertical->code(), 'target' => $target->id]) }}" class="underline">thu thập dữ liệu</a> trước.
        </div>
    @else

    <form method="POST"
          action="{{ route('deployment.export.target', ['vertical' => $vertical->code(), 'target' => $target->id]) }}">
        @csrf

        {{-- Toolbar --}}
        <div class="flex items-center gap-3 mb-4">
            <button type="button" @click="selectAll(true)"
                    class="btn btn-ghost btn-xs">✓ Chọn tất cả</button>
            <button type="button" @click="selectAll(false)"
                    class="btn btn-ghost btn-xs">✗ Bỏ chọn tất cả</button>
            <span class="text-xs text-base-content/40 ml-auto">
                <span x-text="selectedCount()"></span> trường được chọn
            </span>
        </div>

        {{-- Field groups --}}
        <div class="space-y-4 mb-6">
            @foreach($catalog as $gi => $group)
            <div class="card bg-base-100 border border-base-200 shadow-sm">
                <div class="card-body py-3 px-4">
                    {{-- Group header --}}
                    <div class="flex items-center gap-2 mb-2">
                        <label class="flex items-center gap-2 cursor-pointer">
                            <input type="checkbox"
                                   class="checkbox checkbox-sm"
                                   @change="toggleGroup('group_{{ $gi }}', $event.target.checked)"
                                   :checked="isGroupAllSelected('group_{{ $gi }}')">
                            <span class="font-semibold text-sm">{{ $group['group'] }}</span>
                        </label>
                        <span class="badge badge-outline badge-xs ml-auto">
                            {{ count($group['fields']) }} trường
                        </span>
                    </div>

                    {{-- Fields --}}
                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-1 pl-1">
                        @foreach($group['fields'] as $fi => $field)
                        <label class="flex items-center gap-2 cursor-pointer py-1 hover:bg-base-200 px-1.5 rounded text-sm">
                            <input type="checkbox"
                                   name="fields[]"
                                   value="{{ $field['source'] }}"
                                   class="checkbox checkbox-xs"
                                   data-group="group_{{ $gi }}"
                                   x-model="selected['{{ $field['source'] }}']">
                            <span class="truncate text-sm">{{ $field['label'] }}</span>
                        </label>
                        @endforeach
                    </div>
                </div>
            </div>
            @endforeach
        </div>

        {{-- Export button --}}
        <div class="flex gap-3 items-center">
            <button type="submit"
                    class="btn btn-primary gap-2"
                    :disabled="selectedCount() === 0">
                ⬇ Xuất Excel
            </button>
            <span class="text-xs text-base-content/40">
                File .xlsx tải về ngay, không cần chờ
            </span>
        </div>

    </form>

    @endif
</div>

@push('scripts')
<script>
function fieldPicker() {
    // Pre-select all fields by default
    const allSources = @json(collect($catalog)->flatMap(fn($g) => collect($g['fields'])->pluck('source'))->values());

    const initial = {};
    allSources.forEach(s => initial[s] = true);

    return {
        selected: initial,

        selectedCount() {
            return Object.values(this.selected).filter(Boolean).length;
        },

        selectAll(val) {
            Object.keys(this.selected).forEach(k => this.selected[k] = val);
        },

        toggleGroup(groupId, val) {
            document.querySelectorAll(`[data-group="${groupId}"]`).forEach(el => {
                this.selected[el.value] = val;
            });
        },

        isGroupAllSelected(groupId) {
            const els = document.querySelectorAll(`[data-group="${groupId}"]`);
            if (!els.length) return false;
            return Array.from(els).every(el => this.selected[el.value]);
        },
    };
}
</script>
@endpush

@endsection
