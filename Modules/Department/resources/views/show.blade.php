@extends('layouts.backend')
@section('title', 'Phòng ban: ' . $department->name)


@section('content')

{{-- ── Page header ──────────────────────────────────────────────────────── --}}
<div class="flex flex-wrap items-start justify-between gap-4 mb-6">
    <div class="flex items-center gap-4">
        <div class="w-12 h-12 rounded-xl bg-primary/10 flex items-center justify-center">
            <svg class="w-6 h-6 text-primary" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0z"/>
            </svg>
        </div>
        <div>
            <div class="flex items-center gap-2 flex-wrap">
                <h1 class="text-2xl font-bold text-base-content">{{ $department->name }}</h1>
                <span class="badge badge-sm badge-soft {{ $department->status->badgeClass() }}">{{ $department->status->label() }}</span>
                @if($department->function)
                <span class="badge badge-sm badge-soft {{ $department->function->badgeClass() }}">{{ $department->function->label() }}</span>
                @endif
            </div>
            <div class="flex items-center gap-2 mt-0.5 flex-wrap">
                <span class="text-sm text-base-content/50 font-mono">{{ $department->code }}</span>
                @if($department->depth > 0)
                <span class="text-base-content/30">·</span>
                <span class="text-xs text-base-content/50">Cấp {{ $department->depth }}</span>
                @endif
                @if($department->branch)
                <span class="text-base-content/30">·</span>
                <span class="text-xs text-base-content/50">{{ $department->branch->name }}</span>
                @endif
            </div>
        </div>
    </div>
    <div class="flex gap-2">
        <a href="{{ route('backend.departments.index') }}" class="btn btn-ghost btn-sm">
            <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18"/>
            </svg>
            Danh sách
        </a>
        @can('update', $department)
        <a href="{{ route('backend.departments.edit', $department) }}" class="btn btn-primary btn-sm gap-1.5">
            <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"/>
            </svg>
            Sửa
        </a>
        @endcan
    </div>
</div>

<div class="grid grid-cols-1 lg:grid-cols-3 gap-6">

    {{-- ── Main info ────────────────────────────────────────────────── --}}
    <div class="lg:col-span-2 space-y-6">

        {{-- Thông tin cơ bản --}}
        <div class="card bg-base-100 shadow-sm border border-base-200">
            <div class="card-body">
                <h2 class="font-semibold mb-3">Thông tin cơ bản</h2>
                <dl class="grid grid-cols-1 sm:grid-cols-2 gap-x-6 gap-y-3 text-sm">
                    <div>
                        <dt class="text-base-content/50 text-xs">Tên phòng ban</dt>
                        <dd class="font-medium mt-0.5">{{ $department->name }}</dd>
                    </div>
                    <div>
                        <dt class="text-base-content/50 text-xs">Mã phòng ban</dt>
                        <dd class="font-mono font-medium mt-0.5">{{ $department->code }}</dd>
                    </div>
                    <div>
                        <dt class="text-base-content/50 text-xs">Trạng thái</dt>
                        <dd class="mt-0.5">
                            <span class="badge badge-sm badge-soft {{ $department->status->badgeClass() }}">{{ $department->status->label() }}</span>
                        </dd>
                    </div>
                    <div>
                        <dt class="text-base-content/50 text-xs">Chức năng</dt>
                        <dd class="mt-0.5">
                            @if($department->function)
                            <span class="badge badge-sm badge-soft {{ $department->function->badgeClass() }}">{{ $department->function->label() }}</span>
                            @else
                            <span class="text-base-content/40 text-xs">—</span>
                            @endif
                        </dd>
                    </div>
                    @if($department->branch)
                    <div>
                        <dt class="text-base-content/50 text-xs">Chi nhánh</dt>
                        <dd class="mt-0.5">
                            <a href="{{ route('backend.branches.show', $department->branch) }}" class="link link-primary text-sm">
                                {{ $department->branch->name }}
                            </a>
                            <span class="text-base-content/40 font-mono text-xs ml-1">({{ $department->branch->code }})</span>
                        </dd>
                    </div>
                    @else
                    <div>
                        <dt class="text-base-content/50 text-xs">Chi nhánh</dt>
                        <dd class="text-base-content/40 text-xs mt-0.5">Toàn tổ chức</dd>
                    </div>
                    @endif
                    @if($department->parent)
                    <div>
                        <dt class="text-base-content/50 text-xs">Phòng ban cha</dt>
                        <dd class="mt-0.5">
                            <a href="{{ route('backend.departments.show', $department->parent) }}" class="link link-primary text-sm">
                                {{ $department->parent->name }}
                            </a>
                            <span class="text-base-content/40 font-mono text-xs ml-1">({{ $department->parent->code }})</span>
                        </dd>
                    </div>
                    @endif
                    <div>
                        <dt class="text-base-content/50 text-xs">Cấp trong cây</dt>
                        <dd class="font-medium mt-0.5">{{ $department->depth }}</dd>
                    </div>
                    @if($department->budget_code)
                    <div>
                        <dt class="text-base-content/50 text-xs">Mã trung tâm chi phí</dt>
                        <dd class="font-mono font-medium mt-0.5">{{ $department->budget_code }}</dd>
                    </div>
                    @endif
                    @if($department->headcount_limit)
                    <div>
                        <dt class="text-base-content/50 text-xs">Biên chế tối đa</dt>
                        <dd class="font-medium mt-0.5">{{ $department->headcount_limit }} người</dd>
                    </div>
                    @endif
                    @if($department->effective_from)
                    <div>
                        <dt class="text-base-content/50 text-xs">Ngày thành lập</dt>
                        <dd class="mt-0.5">{{ $department->effective_from->format('d/m/Y') }}</dd>
                    </div>
                    @endif
                    @if($department->effective_to)
                    <div>
                        <dt class="text-base-content/50 text-xs">Ngày giải thể</dt>
                        <dd class="mt-0.5">{{ $department->effective_to->format('d/m/Y') }}</dd>
                    </div>
                    @endif
                </dl>
            </div>
        </div>

        {{-- Mô tả --}}
        @if($department->description)
        <div class="card bg-base-100 shadow-sm border border-base-200">
            <div class="card-body">
                <h2 class="font-semibold mb-3">Chức năng nhiệm vụ</h2>
                <p class="text-sm text-base-content/80 whitespace-pre-line">{{ $department->description }}</p>
            </div>
        </div>
        @endif

        {{-- Liên hệ nội bộ --}}
        @if($department->internal_phone || $department->internal_email)
        <div class="card bg-base-100 shadow-sm border border-base-200">
            <div class="card-body">
                <h2 class="font-semibold mb-3">Liên hệ nội bộ</h2>
                <dl class="grid grid-cols-1 sm:grid-cols-2 gap-x-6 gap-y-3 text-sm">
                    @if($department->internal_phone)
                    <div>
                        <dt class="text-base-content/50 text-xs">Điện thoại nội bộ</dt>
                        <dd class="font-medium mt-0.5">{{ $department->internal_phone }}</dd>
                    </div>
                    @endif
                    @if($department->internal_email)
                    <div>
                        <dt class="text-base-content/50 text-xs">Email nội bộ</dt>
                        <dd class="font-medium mt-0.5">
                            <a href="mailto:{{ $department->internal_email }}" class="link link-primary">{{ $department->internal_email }}</a>
                        </dd>
                    </div>
                    @endif
                </dl>
            </div>
        </div>
        @endif

        {{-- Sáp nhập --}}
        @if($department->status->value === 'merged' && $department->mergedInto)
        <div class="card bg-base-100 shadow-sm border border-warning/30">
            <div class="card-body">
                <h2 class="font-semibold mb-3 text-warning">Đã sáp nhập vào</h2>
                <a href="{{ route('backend.departments.show', $department->mergedInto) }}"
                   class="flex items-center gap-3 p-3 rounded-lg bg-base-200/50 hover:bg-base-200 transition-colors">
                    <div>
                        <p class="font-medium text-sm">{{ $department->mergedInto->name }}</p>
                        <p class="text-xs text-base-content/50 font-mono">{{ $department->mergedInto->code }}</p>
                    </div>
                </a>
            </div>
        </div>
        @endif

        {{-- Phòng ban con --}}
        @if($department->children->count() > 0)
        <div class="card bg-base-100 shadow-sm border border-base-200">
            <div class="card-body">
                <h2 class="font-semibold mb-3">Phòng ban con ({{ $department->children->count() }})</h2>
                <div class="space-y-2">
                    @foreach($department->children as $child)
                    <a href="{{ route('backend.departments.show', $child) }}"
                       class="flex items-center justify-between p-3 rounded-lg bg-base-200/50 hover:bg-base-200 transition-colors">
                        <div class="flex items-center gap-3">
                            @if($child->function)
                            <span class="badge badge-sm badge-soft {{ $child->function->badgeClass() }}">{{ $child->function->label() }}</span>
                            @endif
                            <div>
                                <p class="font-medium text-sm">{{ $child->name }}</p>
                                <p class="text-xs text-base-content/50 font-mono">{{ $child->code }}</p>
                            </div>
                        </div>
                        <span class="badge badge-sm badge-soft {{ $child->status->badgeClass() }}">{{ $child->status->label() }}</span>
                    </a>
                    @endforeach
                </div>
            </div>
        </div>
        @endif

    </div>

    {{-- ── Sidebar ───────────────────────────────────────────────────── --}}
    <div class="space-y-4">

        {{-- Audit info --}}
        <div class="card bg-base-100 shadow-sm border border-base-200">
            <div class="card-body gap-3 text-xs text-base-content/60">
                <h3 class="font-semibold text-sm text-base-content">Audit</h3>
                @if($department->createdBy)
                <div>
                    <span class="opacity-70">Tạo bởi:</span>
                    <span class="font-medium text-base-content ml-1">{{ $department->createdBy->name }}</span>
                </div>
                @endif
                <div>
                    <span class="opacity-70">Ngày tạo:</span>
                    <span class="font-medium text-base-content ml-1">{{ $department->created_at?->format('d/m/Y H:i') }}</span>
                </div>
                @if($department->updatedBy)
                <div>
                    <span class="opacity-70">Sửa bởi:</span>
                    <span class="font-medium text-base-content ml-1">{{ $department->updatedBy->name }}</span>
                </div>
                @endif
                <div>
                    <span class="opacity-70">Cập nhật:</span>
                    <span class="font-medium text-base-content ml-1">{{ $department->updated_at?->format('d/m/Y H:i') }}</span>
                </div>
                <div class="pt-1 border-t border-base-200">
                    <span class="opacity-70">UUID:</span>
                    <p class="font-mono text-[10px] break-all mt-0.5 text-base-content/40">{{ $department->uuid }}</p>
                </div>
            </div>
        </div>

        {{-- Danger zone --}}
        @can('delete', $department)
        @if($department->children->count() === 0 && $department->status->value !== 'active')
        <div class="card bg-base-100 shadow-sm border border-error/30">
            <div class="card-body gap-3">
                <h3 class="font-semibold text-sm text-error">Khu vực nguy hiểm</h3>
                <form method="POST" action="{{ route('backend.departments.destroy', $department) }}"
                      onsubmit="return confirm('Bạn chắc chắn muốn xóa phòng ban này?')">
                    @csrf
                    @method('DELETE')
                    <button type="submit" class="btn btn-error btn-sm btn-outline w-full gap-1.5">
                        <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/>
                        </svg>
                        Xóa phòng ban
                    </button>
                </form>
            </div>
        </div>
        @endif
        @endcan

    </div>

</div>
@endsection
