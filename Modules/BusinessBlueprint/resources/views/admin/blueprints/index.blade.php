@extends('layouts.backend')
@section('title', 'Danh sách Blueprint')

@section('content')
<div x-data="{ confirmDelete: null }">

    @foreach(['success','error'] as $type)
        @if(session($type))
        <div x-data="{ show: true }" x-show="show" x-init="setTimeout(() => show = false, 4000)"
             x-transition.opacity.duration.500ms
             class="alert alert-{{ $type }} mb-4 text-sm">
            <span>{{ session($type) }}</span>
            <button @click="show = false" class="btn btn-ghost btn-xs ml-auto">✕</button>
        </div>
        @endif
    @endforeach
    @if ($errors->any())
    <div class="alert alert-error mb-4 text-sm">
        <ul class="list-disc list-inside">@foreach($errors->all() as $e)<li>{{ $e }}</li>@endforeach</ul>
    </div>
    @endif

    <div class="flex flex-wrap items-center justify-between gap-3 mb-5">
        <div>
            <h1 class="text-2xl font-bold text-base-content">Danh sách Blueprint</h1>
            <p class="text-sm text-base-content/50 mt-0.5">
                Thiết kế nghiệp vụ (Outcome/Capability/Workflow/Phase/Checklist/Resource/AI/Analytics) cho từng Business Solution.
            </p>
        </div>
        @can(\App\Enums\PermissionEnum::BLUEPRINT_CREATE->value)
        <a href="{{ route('business_blueprint.admin.create') }}" class="btn btn-primary btn-sm gap-1.5">
            <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/>
            </svg>
            Thêm Blueprint
        </a>
        @endcan
    </div>

    <form method="GET" class="flex flex-wrap gap-2 mb-5">
        <select name="business_solution_id" class="select select-bordered select-sm">
            <option value="">— Tất cả Business Solution —</option>
            @foreach($businessSolutions as $solution)
            <option value="{{ $solution->id }}" @selected(request('business_solution_id') == $solution->id)>{{ $solution->name }}</option>
            @endforeach
        </select>
        <button class="btn btn-sm btn-neutral">Lọc</button>
        @if(request('business_solution_id'))
        <a href="{{ route('business_blueprint.admin.index') }}" class="btn btn-sm btn-ghost">Xoá lọc</a>
        @endif
    </form>

    <div class="card bg-base-100 shadow-sm border border-base-200 overflow-hidden">
        <div class="overflow-x-auto">
            <table class="table table-sm">
                <thead class="bg-base-200/60 text-xs uppercase tracking-wide">
                    <tr>
                        <th>Blueprint</th>
                        <th>Business Solution</th>
                        <th class="text-center">Version hiện hành</th>
                        <th class="text-center">Trạng thái</th>
                        <th class="w-40"></th>
                    </tr>
                </thead>
                <tbody>
                @forelse ($blueprints as $blueprint)
                <tr class="hover">
                    <td>
                        <span class="font-medium text-sm">{{ $blueprint->name }}</span>
                        <div class="text-xs text-base-content/40 font-mono">{{ $blueprint->code }}</div>
                    </td>
                    <td class="text-sm text-base-content/60">{{ $blueprint->businessSolution?->name }}</td>
                    <td class="text-center">
                        <span class="badge badge-ghost badge-sm font-mono">{{ $blueprint->currentVersion?->version ?? '—' }}</span>
                    </td>
                    <td class="text-center">
                        @php
                            $badgeClass = match($blueprint->status) {
                                'published' => 'badge-success',
                                'archived'  => 'badge-ghost',
                                default     => 'badge-warning',
                            };
                        @endphp
                        <span class="badge {{ $badgeClass }} badge-sm">{{ $blueprint->status }}</span>
                    </td>
                    <td>
                        <div class="flex gap-1">
                            <a href="{{ route('business_blueprint.admin.versions.index', $blueprint) }}"
                               class="btn btn-ghost btn-xs" title="Version Manager">Versions</a>
                            @can(\App\Enums\PermissionEnum::BLUEPRINT_EDIT->value)
                            <a href="{{ route('business_blueprint.admin.edit', $blueprint) }}"
                               class="btn btn-ghost btn-xs btn-square" title="Sửa">
                                <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"/>
                                </svg>
                            </a>
                            @endcan
                            @can(\App\Enums\PermissionEnum::BLUEPRINT_DELETE->value)
                            <button @click="confirmDelete = {{ $blueprint->id }}" class="btn btn-ghost btn-xs btn-square text-error" title="Xóa">
                                <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/>
                                </svg>
                            </button>
                            @endcan
                        </div>
                    </td>
                </tr>
                @empty
                <tr>
                    <td colspan="5" class="text-center py-8 text-base-content/40">Chưa có Blueprint nào.</td>
                </tr>
                @endforelse
                </tbody>
            </table>
        </div>
    </div>

    <div x-cloak class="modal" :class="{ 'modal-open': confirmDelete !== null }">
        <div class="modal-box max-w-sm">
            <h3 class="font-bold text-base mb-2">Xóa Blueprint?</h3>
            <p class="text-sm text-base-content/70 mb-4">Không thể xóa nếu Blueprint đã có version publish/archive.</p>
            <div class="modal-action gap-2">
                <button @click="confirmDelete = null" class="btn btn-ghost btn-sm">Hủy</button>
                @foreach ($blueprints as $blueprint)
                <form x-show="confirmDelete === {{ $blueprint->id }}" method="POST"
                      action="{{ route('business_blueprint.admin.destroy', $blueprint) }}">
                    @csrf @method('DELETE')
                    <button type="submit" class="btn btn-error btn-sm">Xóa</button>
                </form>
                @endforeach
            </div>
        </div>
        <div @click="confirmDelete = null" class="modal-backdrop"></div>
    </div>

</div>
@endsection
