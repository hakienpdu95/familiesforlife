@extends('layouts.backend')
@section('title', 'Quản lý Tags')


@section('content')

{{-- Delete form — ngoài để tránh nested form --}}
@can('delete', \Modules\Lead\Models\LeadTagDefinition::class)
<form id="form-delete-tag" method="POST" class="hidden">
    @csrf @method('DELETE')
</form>
@endcan

{{-- Page header --}}
<div class="flex items-center justify-between mb-6">
    <div>
        <h1 class="text-2xl font-bold text-base-content">Quản lý Tags</h1>
        <p class="text-sm text-base-content/50 mt-0.5">Tạo và quản lý nhãn phân loại cơ hội</p>
    </div>
    @can('create', \Modules\Lead\Models\LeadTagDefinition::class)
    <button type="button" class="btn btn-primary btn-sm gap-1.5"
            onclick="document.getElementById('create-modal').showModal()">
        <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/>
        </svg>
        Thêm tag
    </button>
    @endcan
</div>

@if(session('success'))
<div class="alert alert-success text-sm py-2.5 px-4 mb-4 flex items-center gap-2">
    <svg class="w-4 h-4 shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor">
        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/>
    </svg>
    {{ session('success') }}
</div>
@endif

{{-- Tag list --}}
<div class="card bg-base-100 shadow-sm border border-base-200">
    <div class="card-body p-0">

        @if($tags->isEmpty())
        <div class="py-16 text-center text-base-content/40">
            <svg class="w-10 h-10 mx-auto mb-3 opacity-30" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M7 7h.01M7 3h5c.512 0 1.024.195 1.414.586l7 7a2 2 0 010 2.828l-7 7a2 2 0 01-2.828 0l-7-7A1.994 1.994 0 013 12V7a4 4 0 014-4z"/>
            </svg>
            <p class="text-sm">Chưa có tag nào. Tạo tag đầu tiên để phân loại cơ hội.</p>
        </div>

        @else
        <table class="table table-sm">
            <thead>
                <tr class="text-xs text-base-content/50 uppercase tracking-wide">
                    <th class="w-1/2">Tên tag</th>
                    <th>Màu</th>
                    <th class="text-right">Thao tác</th>
                </tr>
            </thead>
            <tbody>
                @foreach($tags as $tag)
                <tr class="hover">
                    <td>
                        <span class="badge badge-sm font-medium text-white"
                              style="background: {{ $tag->color }}">
                            {{ $tag->name }}
                        </span>
                    </td>
                    <td>
                        <span class="inline-flex items-center gap-1.5 text-xs text-base-content/60">
                            <span class="w-4 h-4 rounded-full border border-base-200 shrink-0"
                                  style="background: {{ $tag->color }}"></span>
                            {{ $tag->color }}
                        </span>
                    </td>
                    <td class="text-right">
                        <div class="flex justify-end gap-1">
                            @can('update', $tag)
                            <a href="{{ route('lead.tags.edit', $tag) }}"
                               class="btn btn-ghost btn-xs gap-1">
                                <svg class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"/>
                                </svg>
                                Sửa
                            </a>
                            @endcan
                            @can('delete', $tag)
                            <button type="button"
                                    onclick="confirmDeleteTag('{{ route('lead.tags.destroy', $tag) }}', '{{ addslashes($tag->name) }}')"
                                    class="btn btn-ghost btn-xs text-error gap-1">
                                <svg class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/>
                                </svg>
                                Xóa
                            </button>
                            @endcan
                        </div>
                    </td>
                </tr>
                @endforeach
            </tbody>
        </table>
        @endif

    </div>
</div>

{{-- Create modal --}}
@can('create', \Modules\Lead\Models\LeadTagDefinition::class)
<dialog id="create-modal" class="modal">
    <div class="modal-box max-w-sm">
        <h3 class="font-bold text-base mb-4">Thêm tag mới</h3>
        <form method="POST" action="{{ route('lead.tags.store') }}" x-data>
            @csrf
            @include('lead::tags._form', ['tag' => null])
            <div class="modal-action mt-2">
                <button type="submit" class="btn btn-primary btn-sm gap-1.5">
                    <svg class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/>
                    </svg>
                    Tạo tag
                </button>
                <button type="button" class="btn btn-ghost btn-sm"
                        onclick="document.getElementById('create-modal').close()">Hủy</button>
            </div>
        </form>
    </div>
    <form method="dialog" class="modal-backdrop"><button>close</button></form>
</dialog>
@endcan

{{-- Delete confirm modal --}}
@can('delete', \Modules\Lead\Models\LeadTagDefinition::class)
<dialog id="deleteTagModal" class="modal">
    <div class="modal-box max-w-sm">
        <h3 class="font-bold text-lg text-error">Xác nhận xóa</h3>
        <p class="py-3 text-sm text-base-content/70">
            Bạn có chắc muốn xóa tag
            <strong id="deleteTagName" class="text-base-content"></strong>?
        </p>
        <p class="text-xs text-error/70">Tất cả cơ hội đang dùng tag này sẽ bị gỡ tag.</p>
        <div class="modal-action mt-4">
            <button class="btn btn-error btn-sm gap-1.5"
                    onclick="document.getElementById('form-delete-tag').submit()">
                <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/>
                </svg>
                Xóa
            </button>
            <button class="btn btn-ghost btn-sm" onclick="deleteTagModal.close()">Hủy</button>
        </div>
    </div>
    <form method="dialog" class="modal-backdrop"><button>close</button></form>
</dialog>
@endcan

{{-- Mở lại create modal nếu server trả về lỗi validation --}}
@if($errors->any())
<script>document.getElementById('create-modal')?.showModal();</script>
@endif

@endsection

@push('scripts')
<script>
// Cập nhật text preview badge khi gõ tên tag
document.querySelectorAll('[name="name"]').forEach(function(input) {
    input.addEventListener('input', function() {
        var preview = input.closest('form').querySelector('[x-bind\\:style]');
        if (preview) preview.textContent = input.value || 'Preview';
    });
});
// Đồng bộ radio color → hidden input (để form submit đúng giá trị)
document.querySelectorAll('[name="color"][type="radio"]').forEach(function(r) {
    r.addEventListener('change', function() {
        var hidden = r.closest('form').querySelector('#colorHidden');
        if (hidden) hidden.value = r.value;
    });
});
// Confirm delete handler
window.confirmDeleteTag = function(url, name) {
    document.getElementById('form-delete-tag').action = url;
    document.getElementById('deleteTagName').textContent = '"' + name + '"';
    document.getElementById('deleteTagModal').showModal();
};
</script>
@endpush
