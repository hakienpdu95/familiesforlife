@extends('layouts.backend')
@section('title', 'Export toàn dự án — ' . $project->name)

@section('content')
<div class="max-w-2xl" x-data="fieldPicker()">

    <div class="mb-6">
        <div class="text-sm text-base-content/50 mb-1">
            <a href="{{ route('deployment.projects.index', ['vertical' => $vertical->code()]) }}"
               class="hover:underline">← Quay lại dự án</a>
        </div>
        <h1 class="text-xl font-bold">📊 Export toàn dự án</h1>
        <p class="text-sm text-base-content/50 mt-0.5">
            Dự án: <strong>{{ $project->name }}</strong>
            · <span class="text-warning">{{ $targetCount }} tổ chức</span> đã qua phase draft
            · Mỗi dòng = 1 tổ chức
        </p>
    </div>

    @if(empty($catalog) || ! $anchor)
        <div class="alert alert-warning text-sm">
            Chưa có target nào hoàn thành thu thập dữ liệu trong dự án này.
        </div>
    @else

    <form method="POST"
          action="{{ route('deployment.export.project', ['vertical' => $vertical->code(), 'project' => $project->id]) }}">
        @csrf

        <div class="flex items-center gap-3 mb-4">
            <button type="button" @click="selectAll(true)"  class="btn btn-ghost btn-xs">✓ Chọn tất cả</button>
            <button type="button" @click="selectAll(false)" class="btn btn-ghost btn-xs">✗ Bỏ chọn</button>
            <span class="text-xs text-base-content/40 ml-auto">
                <span x-text="selectedCount()"></span> trường · {{ $targetCount }} dòng
            </span>
        </div>

        <div class="space-y-4 mb-6">
            @foreach($catalog as $gi => $group)
            <div class="card bg-base-100 border border-base-200 shadow-sm">
                <div class="card-body py-3 px-4">
                    <div class="flex items-center gap-2 mb-2">
                        <label class="flex items-center gap-2 cursor-pointer">
                            <input type="checkbox"
                                   class="checkbox checkbox-sm"
                                   @change="toggleGroup('group_{{ $gi }}', $event.target.checked)"
                                   :checked="isGroupAllSelected('group_{{ $gi }}')">
                            <span class="font-semibold text-sm">{{ $group['group'] }}</span>
                        </label>
                        <span class="badge badge-outline badge-xs ml-auto">{{ count($group['fields']) }} trường</span>
                    </div>
                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-1 pl-1">
                        @foreach($group['fields'] as $fi => $field)
                        <label class="flex items-center gap-2 cursor-pointer py-1 hover:bg-base-200 px-1.5 rounded">
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

        <div class="flex gap-3 items-center">
            <button type="submit" class="btn btn-primary gap-2" :disabled="selectedCount() === 0">
                ⬇ Xuất Excel ({{ $targetCount }} dòng)
            </button>
            <span class="text-xs text-base-content/40">Tải về ngay, không cần chờ</span>
        </div>
    </form>

    @endif
</div>

@push('scripts')
<script>
function fieldPicker() {
    const allSources = @json(collect($catalog)->flatMap(fn($g) => collect($g['fields'])->pluck('source'))->values());
    const initial = {};
    allSources.forEach(s => initial[s] = true);

    return {
        selected: initial,
        selectedCount() { return Object.values(this.selected).filter(Boolean).length; },
        selectAll(val)  { Object.keys(this.selected).forEach(k => this.selected[k] = val); },
        toggleGroup(groupId, val) {
            document.querySelectorAll(`[data-group="${groupId}"]`).forEach(el => {
                this.selected[el.value] = val;
            });
        },
        isGroupAllSelected(groupId) {
            const els = document.querySelectorAll(`[data-group="${groupId}"]`);
            return els.length && Array.from(els).every(el => this.selected[el.value]);
        },
    };
}
</script>
@endpush

@endsection
