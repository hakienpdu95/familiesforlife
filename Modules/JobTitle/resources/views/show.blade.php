@extends('layouts.backend')
@section('title', $jobTitle->name)


@section('content')
<div>

{{-- Page header --}}
<div class="flex flex-wrap items-start justify-between gap-4 mb-6">
    <div class="flex items-center gap-4">
        <div class="w-14 h-14 rounded-xl bg-primary/10 flex items-center justify-center text-primary font-bold text-xl">
            {{ mb_substr($jobTitle->name, 0, 1) }}
        </div>
        <div>
            <div class="flex items-center gap-2">
                <h1 class="text-2xl font-bold text-base-content">{{ $jobTitle->name }}</h1>
                @if($jobTitle->is_system)
                    <span class="badge badge-info badge-sm">Hệ thống</span>
                @endif
                @if($jobTitle->is_locked)
                    <span class="badge badge-warning badge-sm gap-1">
                        <svg class="w-3 h-3" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z"/>
                        </svg>
                        Đã khóa
                    </span>
                @endif
                @if(! $jobTitle->is_active)
                    <span class="badge badge-ghost badge-sm">Vô hiệu</span>
                @endif
            </div>
            <p class="text-sm text-base-content/50 mt-0.5 font-mono">{{ $jobTitle->code }}</p>
        </div>
    </div>
    <div class="flex items-center gap-2">
        @can('update', $jobTitle)
        <a href="{{ route('backend.job-titles.edit', $jobTitle) }}" class="btn btn-ghost btn-sm gap-1.5">
            <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"/>
            </svg>
            Chỉnh sửa
        </a>
        @endcan
        <a href="{{ route('backend.job-titles.index') }}" class="btn btn-ghost btn-sm gap-1.5">
            <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18"/>
            </svg>
            Quay lại
        </a>
    </div>
</div>

@if(session('success'))
<div class="alert alert-success py-3 px-4 mb-5 text-sm">{{ session('success') }}</div>
@endif

<div class="grid grid-cols-1 xl:grid-cols-[1fr_260px] gap-6 items-start">

    {{-- ── Card thông tin chính ─────────────────────────────────────────── --}}
    <div class="card bg-base-100 shadow-sm border border-base-200">
        <div class="card-body">
            <h2 class="card-title text-base mb-4">Thông tin chức danh</h2>
            <div class="grid grid-cols-1 sm:grid-cols-2 gap-x-8 gap-y-4">

                <div>
                    <p class="text-xs text-base-content/40 uppercase tracking-wide mb-1">Tên chức danh</p>
                    <p class="text-sm font-medium">{{ $jobTitle->name }}</p>
                </div>

                <div>
                    <p class="text-xs text-base-content/40 uppercase tracking-wide mb-1">Mã chức danh</p>
                    <p class="text-sm font-mono font-semibold">{{ $jobTitle->code }}</p>
                </div>

                <div>
                    <p class="text-xs text-base-content/40 uppercase tracking-wide mb-1">Nhóm chức danh</p>
                    <span class="badge badge-sm badge-soft {{ $jobTitle->category->badgeClass() }}">
                        {{ $jobTitle->category->label() }}
                    </span>
                </div>

                <div>
                    <p class="text-xs text-base-content/40 uppercase tracking-wide mb-1">Cấp bậc</p>
                    <div class="flex items-center gap-2">
                        <span class="badge badge-ghost badge-sm font-mono font-bold">{{ $jobTitle->level }}</span>
                        <span class="text-xs text-base-content/40">/ 20</span>
                        <div class="flex-1 max-w-24">
                            <progress class="progress progress-primary h-1.5" value="{{ $jobTitle->level }}" max="20"></progress>
                        </div>
                    </div>
                </div>

                @if($jobTitle->description)
                <div class="sm:col-span-2">
                    <p class="text-xs text-base-content/40 uppercase tracking-wide mb-1">Mô tả</p>
                    <p class="text-sm text-base-content/80 whitespace-pre-line">{{ $jobTitle->description }}</p>
                </div>
                @endif

            </div>
        </div>
    </div>

    {{-- ── Sidebar: Meta ─────────────────────────────────────────────────── --}}
    <div class="space-y-4">
        <div class="card bg-base-100 shadow-sm border border-base-200">
            <div class="card-body gap-3">
                <h3 class="font-semibold text-sm">Thông tin hệ thống</h3>

                <div class="space-y-3 text-sm">
                    <div class="flex items-center justify-between">
                        <span class="text-base-content/50">Trạng thái</span>
                        @if($jobTitle->is_active)
                            <span class="badge badge-success badge-sm">Đang dùng</span>
                        @else
                            <span class="badge badge-ghost badge-sm">Vô hiệu</span>
                        @endif
                    </div>
                    <div class="flex items-center justify-between">
                        <span class="text-base-content/50">Nguồn gốc</span>
                        @if($jobTitle->is_system)
                            <span class="badge badge-info badge-sm">Hệ thống</span>
                        @else
                            <span class="badge badge-ghost badge-sm">Do org tạo</span>
                        @endif
                    </div>
                    <div class="flex items-center justify-between">
                        <span class="text-base-content/50">Trạng thái khóa</span>
                        @if($jobTitle->is_locked)
                            <span class="badge badge-warning badge-sm">Đã khóa</span>
                        @else
                            <span class="badge badge-ghost badge-sm">Không khóa</span>
                        @endif
                    </div>
                    <div class="divider my-1"></div>
                    <div class="flex items-center justify-between">
                        <span class="text-base-content/50">Ngày tạo</span>
                        <span class="text-xs">{{ $jobTitle->created_at?->format('d/m/Y H:i') }}</span>
                    </div>
                    <div class="flex items-center justify-between">
                        <span class="text-base-content/50">Cập nhật</span>
                        <span class="text-xs">{{ $jobTitle->updated_at?->format('d/m/Y H:i') }}</span>
                    </div>
                </div>
            </div>
        </div>

        @can('delete', $jobTitle)
        <div class="card bg-base-100 shadow-sm border border-error/20">
            <div class="card-body gap-3">
                <h3 class="font-semibold text-sm text-error">Vùng nguy hiểm</h3>
                <p class="text-xs text-base-content/50">Xóa chức danh này vĩnh viễn. Không thể hoàn tác.</p>
                <form method="POST" action="{{ route('backend.job-titles.destroy', $jobTitle) }}"
                      onsubmit="return confirm('Xóa chức danh \'{{ addslashes($jobTitle->name) }}\'? Không thể hoàn tác.')">
                    @csrf
                    @method('DELETE')
                    <button type="submit" class="btn btn-error btn-outline btn-sm w-full gap-1.5">
                        <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/>
                        </svg>
                        Xóa chức danh
                    </button>
                </form>
            </div>
        </div>
        @endcan
    </div>

</div>
</div>
@endsection
