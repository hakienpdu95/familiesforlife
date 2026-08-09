@extends('layouts.backend')
@section('title', 'AI Video Studio')

@section('content')
<div>
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

    <div class="flex flex-wrap items-center justify-between gap-3 mb-5">
        <div>
            <h1 class="text-2xl font-bold text-base-content">AI Video Studio</h1>
            <p class="text-sm text-base-content/50 mt-0.5">Director Prompt Template theo Project → nhiều Shot — soạn prompt để dán sang tool ảnh/video AI ngoài</p>
        </div>
        <a href="{{ route('backend.aivideostudiotemplate.create') }}" class="btn btn-primary btn-sm gap-1.5">
            <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/>
            </svg>
            Tạo project mới
        </a>
    </div>

    {{-- spec §8 — filter theo status (draft/active/archived) + tìm theo name, GET query string. --}}
    <form method="GET" action="{{ route('backend.aivideostudiotemplate.index') }}"
          class="card bg-base-100 shadow-sm border border-base-200 mb-4">
        <div class="card-body py-3 px-4">
            <div class="flex flex-wrap gap-3 items-end">
                <div class="form-control w-72">
                    <label class="label py-0.5"><span class="label-text text-xs font-medium">Tìm theo tên</span></label>
                    <input type="text" name="search" value="{{ $filters['search'] }}"
                           class="input input-sm input-bordered w-full" placeholder="Nhập tên project...">
                </div>
                <div class="form-control w-56">
                    <label class="label py-0.5"><span class="label-text text-xs font-medium">Trạng thái</span></label>
                    <select name="status" class="select select-sm select-bordered w-full">
                        <option value="">— Tất cả —</option>
                        @foreach(['draft' => 'Draft', 'active' => 'Active', 'archived' => 'Archived'] as $key => $label)
                        <option value="{{ $key }}" @selected($filters['status'] === $key)>{{ $label }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="form-control flex-row gap-2">
                    <button type="submit" class="btn btn-primary btn-sm">Lọc</button>
                    @if($filters['search'] || $filters['status'])
                    <a href="{{ route('backend.aivideostudiotemplate.index') }}" class="btn btn-ghost btn-sm text-error">Đặt lại</a>
                    @endif
                </div>
            </div>
        </div>
    </form>

    <div class="card bg-base-100 shadow-sm border border-base-200">
        <div class="overflow-x-auto">
            <table class="table">
                <thead>
                    <tr>
                        <th>Project</th>
                        <th>Trạng thái</th>
                        <th class="text-center">Số shot</th>
                        <th>Người tạo</th>
                        <th>Ngày tạo</th>
                        <th class="text-right">Hành động</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($projects as $project)
                    <tr>
                        <td>
                            <a href="{{ route('backend.aivideostudiotemplate.show', $project) }}" class="link link-hover font-medium">{{ $project->name }}</a>
                            @if($project->description)
                            <p class="text-xs text-base-content/40 truncate max-w-xs">{{ $project->description }}</p>
                            @endif
                        </td>
                        <td>
                            <span class="badge badge-sm {{ match($project->status) {
                                'active' => 'badge-success',
                                'archived' => 'badge-ghost',
                                default => 'badge-warning',
                            } }}">{{ $project->status }}</span>
                        </td>
                        <td class="text-center">{{ $project->shots_count }}</td>
                        <td>{{ $project->createdBy?->name ?? '—' }}</td>
                        <td>{{ $project->created_at->format('d/m/Y H:i') }}</td>
                        <td class="text-right">
                            <div class="flex justify-end gap-1">
                                <a href="{{ route('backend.aivideostudiotemplate.edit', $project) }}" class="btn btn-ghost btn-xs">Sửa</a>
                                <button type="button" class="btn btn-ghost btn-xs text-error"
                                        data-url="{{ route('backend.aivideostudiotemplate.destroy', $project) }}"
                                        data-name="{{ $project->name }}"
                                        data-shots="{{ $project->shots_count }}"
                                        onclick="aivsDeleteProjectConfirm(this.dataset.url, this.dataset.name, this.dataset.shots)">Xoá</button>
                            </div>
                        </td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="6" class="text-center py-16 opacity-40">
                            <svg class="w-12 h-12 mx-auto mb-3" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M15 10l4.553-2.276A1 1 0 0121 8.618v6.764a1 1 0 01-1.447.894L15 14M5 18h8a2 2 0 002-2V8a2 2 0 00-2-2H5a2 2 0 00-2 2v8a2 2 0 002 2z"/></svg>
                            <p class="text-sm">Chưa có project nào</p>
                        </td>
                    </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        @if($projects->hasPages())
        <div class="card-body py-3 px-4 border-t border-base-200">
            {{ $projects->links() }}
        </div>
        @endif
    </div>
</div>

{{-- spec §8 — xoá Project bắt buộc confirm nêu rõ hậu quả cascade xoá toàn bộ shot con. --}}
<dialog id="aivsDeleteProjectModal" class="modal">
    <div class="modal-box max-w-sm">
        <h3 class="font-bold text-lg text-error">Xác nhận xoá project</h3>
        <p class="py-3 text-sm text-base-content/70">
            Xoá project <b id="aivsDeleteProjectName"></b> sẽ xoá VĨNH VIỄN toàn bộ
            <b id="aivsDeleteProjectShots"></b> shot bên trong (cascade). Không thể hoàn tác.
        </p>
        <form id="aivsDeleteProjectForm" method="POST">
            @csrf
            @method('DELETE')
            <div class="modal-action mt-4">
                <button type="submit" class="btn btn-error btn-sm">Xoá</button>
                <button type="button" class="btn btn-ghost btn-sm" onclick="aivsDeleteProjectModal.close()">Hủy</button>
            </div>
        </form>
    </div>
    <form method="dialog" class="modal-backdrop"><button>close</button></form>
</dialog>
@endsection

@push('scripts')
<script>
function aivsDeleteProjectConfirm(url, name, shots) {
    document.getElementById('aivsDeleteProjectForm').action = url;
    document.getElementById('aivsDeleteProjectName').textContent = '"' + name + '"';
    document.getElementById('aivsDeleteProjectShots').textContent = shots;
    document.getElementById('aivsDeleteProjectModal').showModal();
}
</script>
@endpush
