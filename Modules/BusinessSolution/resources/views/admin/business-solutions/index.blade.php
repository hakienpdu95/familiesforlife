@extends('layouts.backend')
@section('title', 'Danh mục Business Solution')

@section('content')
<div x-data="{ confirmDelete: null }">

    {{-- Flash messages --}}
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

    {{-- Header --}}
    <div class="flex flex-wrap items-center justify-between gap-3 mb-5">
        <div>
            <h1 class="text-2xl font-bold text-base-content">Danh mục Business Solution</h1>
            <p class="text-sm text-base-content/50 mt-0.5">
                Danh mục Business Solution tách biệt khỏi Vertical — bảng cha cho Blueprint và Organization Solution.
            </p>
        </div>
        <a href="{{ route('business_solutions.admin.create') }}" class="btn btn-primary btn-sm gap-1.5">
            <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/>
            </svg>
            Thêm Business Solution
        </a>
    </div>

    {{-- Filters --}}
    <form method="GET" class="flex flex-wrap gap-2 mb-5">
        <input type="text" name="q" value="{{ request('q') }}" placeholder="Tìm tên solution..."
               class="input input-bordered input-sm w-56">
        <select name="vertical_id" class="select select-bordered select-sm">
            <option value="">— Tất cả vertical —</option>
            @foreach($verticals as $vertical)
            <option value="{{ $vertical->id }}" @selected(request('vertical_id') == $vertical->id)>{{ $vertical->name }}</option>
            @endforeach
        </select>
        <select name="status" class="select select-bordered select-sm">
            <option value="">— Tất cả trạng thái —</option>
            @foreach(\Modules\BusinessSolution\Enums\BusinessSolutionStatus::cases() as $status)
            <option value="{{ $status->value }}" @selected(request('status') === $status->value)>{{ $status->label() }}</option>
            @endforeach
        </select>
        <button class="btn btn-sm btn-neutral">Lọc</button>
        @if(request('q') || request('vertical_id') || request('status'))
        <a href="{{ route('business_solutions.admin.index') }}" class="btn btn-sm btn-ghost">Xoá lọc</a>
        @endif
    </form>

    {{-- Table --}}
    <div class="card bg-base-100 shadow-sm border border-base-200 overflow-hidden">
        <div class="overflow-x-auto">
            <table class="table table-sm">
                <thead class="bg-base-200/60 text-xs uppercase tracking-wide">
                    <tr>
                        <th>Solution</th>
                        <th>Vertical</th>
                        <th class="text-center">Visibility</th>
                        <th class="text-center">Trạng thái</th>
                        <th class="w-32"></th>
                    </tr>
                </thead>
                <tbody>
                @forelse ($solutions as $solution)
                <tr class="hover">
                    <td>
                        <span class="font-medium text-sm">{{ $solution->name }}</span>
                        <div class="text-xs text-base-content/40 font-mono">{{ $solution->code }}</div>
                    </td>
                    <td class="text-sm text-base-content/60">{{ $solution->vertical?->name }}</td>
                    <td class="text-center">
                        <span class="badge badge-ghost badge-sm">{{ $solution->visibility }}</span>
                    </td>
                    <td class="text-center">
                        @php
                            $badgeClass = match($solution->status) {
                                'published' => 'badge-success',
                                'archived'  => 'badge-ghost',
                                default     => 'badge-warning',
                            };
                        @endphp
                        <span class="badge {{ $badgeClass }} badge-sm">{{ $solution->status }}</span>
                    </td>
                    <td>
                        <div class="flex gap-1">
                            <a href="{{ route('business_solutions.admin.edit', $solution) }}"
                               class="btn btn-ghost btn-xs btn-square" title="Sửa">
                                <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"/>
                                </svg>
                            </a>
                            @if ($solution->status === 'draft')
                            <form method="POST" action="{{ route('business_solutions.admin.publish', $solution) }}">
                                @csrf
                                <button type="submit" class="btn btn-ghost btn-xs" title="Phát hành">Phát hành</button>
                            </form>
                            @endif
                            @if ($solution->status !== 'archived')
                            <form method="POST" action="{{ route('business_solutions.admin.archive', $solution) }}">
                                @csrf
                                <button type="submit" class="btn btn-ghost btn-xs text-warning" title="Lưu trữ">Lưu trữ</button>
                            </form>
                            @endif
                            <button @click="confirmDelete = {{ $solution->id }}" class="btn btn-ghost btn-xs btn-square text-error" title="Xóa">
                                <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/>
                                </svg>
                            </button>
                        </div>
                    </td>
                </tr>
                @empty
                <tr>
                    <td colspan="5" class="text-center py-8 text-base-content/40">Chưa có Business Solution nào.</td>
                </tr>
                @endforelse
                </tbody>
            </table>
        </div>
    </div>

    {{-- Confirm delete modal --}}
    <div x-cloak class="modal" :class="{ 'modal-open': confirmDelete !== null }">
        <div class="modal-box max-w-sm">
            <h3 class="font-bold text-base mb-2">Xóa Business Solution?</h3>
            <p class="text-sm text-base-content/70 mb-4">Không thể xóa nếu solution đã có version.</p>
            <div class="modal-action gap-2">
                <button @click="confirmDelete = null" class="btn btn-ghost btn-sm">Hủy</button>
                @foreach ($solutions as $solution)
                <form x-show="confirmDelete === {{ $solution->id }}" method="POST"
                      action="{{ route('business_solutions.admin.destroy', $solution) }}">
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
