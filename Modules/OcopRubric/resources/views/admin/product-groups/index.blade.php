@extends('layouts.backend')
@section('title', 'Danh mục Bộ sản phẩm OCOP')

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
            <h1 class="text-2xl font-bold text-base-content">Danh mục Bộ sản phẩm OCOP</h1>
            <p class="text-sm text-base-content/50 mt-0.5">
                Phụ lục I, QĐ 26/2026/QĐ-TTg — 6 Ngành, 26 Bộ sản phẩm. Danh mục này là quy định
                chung của Nhà nước, chỉ System Admin được sửa.
            </p>
        </div>
        <a href="{{ route('ocop_rubric.admin.product-groups.create') }}" class="btn btn-primary btn-sm gap-1.5">
            <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/>
            </svg>
            Thêm bộ sản phẩm
        </a>
    </div>

    {{-- Filters --}}
    <form method="GET" class="flex flex-wrap gap-2 mb-5">
        <input type="text" name="q" value="{{ request('q') }}" placeholder="Tìm tên bộ sản phẩm..."
               class="input input-bordered input-sm w-56">
        <select name="industry_code" class="select select-bordered select-sm">
            <option value="">— Tất cả ngành —</option>
            @foreach(['I','II','III','IV','V','VI'] as $code)
            <option value="{{ $code }}" @selected(request('industry_code') === $code)>Ngành {{ $code }}</option>
            @endforeach
        </select>
        <button class="btn btn-sm btn-neutral">Lọc</button>
        @if(request('q') || request('industry_code'))
        <a href="{{ route('ocop_rubric.admin.product-groups.index') }}" class="btn btn-sm btn-ghost">Xoá lọc</a>
        @endif
    </form>

    {{-- Table --}}
    <div class="card bg-base-100 shadow-sm border border-base-200 overflow-hidden">
        <div class="overflow-x-auto">
            <table class="table table-sm">
                <thead class="bg-base-200/60 text-xs uppercase tracking-wide">
                    <tr>
                        <th class="w-12">#</th>
                        <th>Ngành</th>
                        <th>Bộ sản phẩm</th>
                        <th>Nhóm</th>
                        <th>Cơ quan chủ trì</th>
                        <th class="text-center">Yêu cầu mẫu</th>
                        <th class="text-center">Trạng thái</th>
                        <th class="w-24"></th>
                    </tr>
                </thead>
                <tbody>
                @forelse ($groups as $group)
                <tr class="hover">
                    <td class="text-xs text-base-content/50">{{ $group->sort_order }}</td>
                    <td>
                        <span class="badge badge-ghost badge-sm font-mono">{{ $group->industry_code }}</span>
                        <div class="text-xs text-base-content/50 mt-0.5">{{ $group->industry_name }}</div>
                    </td>
                    <td>
                        <span class="font-medium text-sm">{{ $group->name }}</span>
                        <div class="text-xs text-base-content/40 font-mono">{{ $group->code }}</div>
                    </td>
                    <td class="text-sm text-base-content/60">{{ $group->group_label }}</td>
                    <td class="text-sm text-base-content/60">{{ $group->managing_agency ?? '—' }}</td>
                    <td class="text-center">
                        @if ($group->requires_sample_product)
                            <span class="badge badge-success badge-sm">Có</span>
                        @else
                            <span class="badge badge-ghost badge-sm">Không</span>
                        @endif
                    </td>
                    <td class="text-center">
                        @if ($group->is_active)
                            <span class="badge badge-success badge-sm">Active</span>
                        @else
                            <span class="badge badge-ghost badge-sm text-base-content/40">Inactive</span>
                        @endif
                    </td>
                    <td>
                        <div class="flex gap-1">
                            @if ($group->activeRubricVersion)
                            <a href="{{ route('ocop_rubric.admin.product-groups.versions.tree', [$group, $group->activeRubricVersion]) }}"
                               class="btn btn-ghost btn-xs btn-square" title="Xem cây tiêu chí">
                                <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2m-6 9l2 2 4-4"/>
                                </svg>
                            </a>
                            @else
                            <span class="btn btn-ghost btn-xs btn-square opacity-30 cursor-not-allowed" title="Chưa có bộ tiêu chí">
                                <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2m-6 9l2 2 4-4"/>
                                </svg>
                            </span>
                            @endif
                            <a href="{{ route('ocop_rubric.admin.product-groups.edit', $group) }}"
                               class="btn btn-ghost btn-xs btn-square" title="Sửa">
                                <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"/>
                                </svg>
                            </a>
                            <button @click="confirmDelete = {{ $group->id }}" class="btn btn-ghost btn-xs btn-square text-error" title="Xóa">
                                <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/>
                                </svg>
                            </button>
                        </div>
                    </td>
                </tr>
                @empty
                <tr>
                    <td colspan="8" class="text-center py-8 text-base-content/40">Chưa có bộ sản phẩm nào.</td>
                </tr>
                @endforelse
                </tbody>
            </table>
        </div>
    </div>

    {{-- Confirm delete modal --}}
    <div x-cloak class="modal" :class="{ 'modal-open': confirmDelete !== null }">
        <div class="modal-box max-w-sm">
            <h3 class="font-bold text-base mb-2">Xóa bộ sản phẩm?</h3>
            <p class="text-sm text-base-content/70 mb-4">Không thể xóa nếu bộ sản phẩm đã có bộ tiêu chí (rubric version).</p>
            <div class="modal-action gap-2">
                <button @click="confirmDelete = null" class="btn btn-ghost btn-sm">Hủy</button>
                @foreach ($groups as $group)
                <form x-show="confirmDelete === {{ $group->id }}" method="POST"
                      action="{{ route('ocop_rubric.admin.product-groups.destroy', $group) }}">
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
