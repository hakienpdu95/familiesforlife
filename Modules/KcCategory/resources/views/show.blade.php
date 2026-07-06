@extends('layouts.backend')
@section('title', $kcCategory->name)


@section('content')

{{-- ── Page header ──────────────────────────────────────────────────────── --}}
<div class="flex flex-wrap items-start justify-between gap-4 mb-6">
    <div class="flex items-center gap-4 min-w-0">

        {{-- Icon / Color avatar --}}
        <div class="w-12 h-12 rounded-xl flex items-center justify-center text-white text-xl shrink-0 select-none"
             style="background-color: {{ $kcCategory->color_hex ?? '#6366f1' }}">
            @if($kcCategory->icon)
                <i class="{{ $kcCategory->icon }}"></i>
            @else
                <svg class="w-6 h-6" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 7v10a2 2 0 002 2h14a2 2 0 002-2V9a2 2 0 00-2-2h-6l-2-2H5a2 2 0 00-2 2z"/>
                </svg>
            @endif
        </div>

        <div class="min-w-0">
            <h1 class="text-2xl font-bold text-base-content leading-tight">{{ $kcCategory->name }}</h1>
            <div class="flex flex-wrap items-center gap-2 mt-1">
                @if($kcCategory->is_active)
                    <span class="badge badge-success badge-sm">Đang hiển thị</span>
                @else
                    <span class="badge badge-ghost badge-sm">Đang ẩn</span>
                @endif
                <span class="text-xs text-base-content/40 font-mono">{{ $kcCategory->slug }}</span>
                @if($kcCategory->parent)
                    <span class="text-xs text-base-content/50">
                        trong <a href="{{ route('backend.kc-categories.show', $kcCategory->parent) }}" class="hover:text-primary underline underline-offset-2">{{ $kcCategory->parent->name }}</a>
                    </span>
                @endif
            </div>
        </div>
    </div>

    <div class="flex items-center gap-2 shrink-0">
        @can('update', $kcCategory)
        <a href="{{ route('backend.kc-categories.edit', $kcCategory) }}" class="btn btn-primary btn-sm gap-2">
            <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"/>
            </svg>
            Chỉnh sửa
        </a>
        @endcan
    </div>
</div>

{{-- ── Content ───────────────────────────────────────────────────────────── --}}
<div class="grid grid-cols-1 xl:grid-cols-[1fr_268px] gap-6 items-start">

    {{-- ── Main ──────────────────────────────────────────────────────── --}}
    <div class="space-y-5">

        {{-- Thông tin cơ bản --}}
        <div class="card bg-base-100 shadow-sm border border-base-200">
            <div class="card-body">
                <h2 class="card-title text-base mb-4">Thông tin danh mục</h2>
                <dl class="grid grid-cols-1 sm:grid-cols-2 gap-x-6 gap-y-3 text-sm">
                    <div>
                        <dt class="text-xs text-base-content/50 mb-0.5">Tên danh mục</dt>
                        <dd class="font-medium">{{ $kcCategory->name }}</dd>
                    </div>
                    <div>
                        <dt class="text-xs text-base-content/50 mb-0.5">Slug</dt>
                        <dd class="font-mono text-xs bg-base-200 px-2 py-1 rounded inline-block">{{ $kcCategory->slug }}</dd>
                    </div>
                    @if($kcCategory->parent)
                    <div>
                        <dt class="text-xs text-base-content/50 mb-0.5">Danh mục cha</dt>
                        <dd>
                            <a href="{{ route('backend.kc-categories.show', $kcCategory->parent) }}"
                               class="text-primary hover:underline">{{ $kcCategory->parent->name }}</a>
                        </dd>
                    </div>
                    @endif
                    <div>
                        <dt class="text-xs text-base-content/50 mb-0.5">Thứ tự</dt>
                        <dd>{{ $kcCategory->sort_order }}</dd>
                    </div>
                    @if($kcCategory->icon)
                    <div>
                        <dt class="text-xs text-base-content/50 mb-0.5">Icon</dt>
                        <dd class="font-mono text-xs">{{ $kcCategory->icon }}</dd>
                    </div>
                    @endif
                    @if($kcCategory->color_hex)
                    <div>
                        <dt class="text-xs text-base-content/50 mb-0.5">Màu sắc</dt>
                        <dd class="flex items-center gap-2">
                            <span class="w-5 h-5 rounded border border-base-300 shrink-0"
                                  style="background:{{ $kcCategory->color_hex }}"></span>
                            <span class="font-mono text-xs">{{ $kcCategory->color_hex }}</span>
                        </dd>
                    </div>
                    @endif
                    @if($kcCategory->description)
                    <div class="sm:col-span-2">
                        <dt class="text-xs text-base-content/50 mb-0.5">Mô tả</dt>
                        <dd class="text-base-content/80">{{ $kcCategory->description }}</dd>
                    </div>
                    @endif
                </dl>
            </div>
        </div>

        {{-- Danh mục con --}}
        @if($kcCategory->children->isNotEmpty())
        <div class="card bg-base-100 shadow-sm border border-base-200">
            <div class="card-body">
                <h2 class="card-title text-base mb-4">
                    Danh mục con
                    <span class="badge badge-ghost badge-sm">{{ $kcCategory->children->count() }}</span>
                </h2>
                <div class="space-y-2">
                    @foreach($kcCategory->children as $child)
                    <div class="flex items-center justify-between p-3 rounded-lg border border-base-200 hover:bg-base-50 transition-colors">
                        <div class="flex items-center gap-3">
                            @if($child->color_hex)
                            <span class="w-3 h-3 rounded-full shrink-0" style="background:{{ $child->color_hex }}"></span>
                            @endif
                            <div>
                                <a href="{{ route('backend.kc-categories.show', $child) }}"
                                   class="font-medium text-sm hover:text-primary transition-colors">{{ $child->name }}</a>
                                <p class="text-xs text-base-content/40 font-mono">{{ $child->slug }}</p>
                            </div>
                        </div>
                        <div class="flex items-center gap-2">
                            @if(!$child->is_active)
                                <span class="badge badge-ghost badge-xs">Ẩn</span>
                            @endif
                            <span class="text-xs text-base-content/40">{{ $child->children_count }} con</span>
                        </div>
                    </div>
                    @endforeach
                </div>
            </div>
        </div>
        @endif

    </div>

    {{-- ── Sidebar ──────────────────────────────────────────────────── --}}
    <div class="space-y-4">

        <div class="card bg-base-100 shadow-sm border border-base-200">
            <div class="card-body p-4 space-y-3">
                <p class="text-xs font-semibold text-base-content/40 uppercase tracking-wide">Thông tin</p>
                <div class="space-y-2 text-xs text-base-content/60">
                    <div class="flex justify-between">
                        <span>Ngày tạo</span>
                        <span>{{ $kcCategory->created_at->format('d/m/Y H:i') }}</span>
                    </div>
                    <div class="flex justify-between">
                        <span>Cập nhật</span>
                        <span>{{ $kcCategory->updated_at->diffForHumans() }}</span>
                    </div>
                </div>
            </div>
        </div>

        @can('delete', $kcCategory)
        <div class="card bg-base-100 shadow-sm border border-error/20">
            <div class="card-body p-4">
                <p class="text-xs font-semibold text-error/60 uppercase tracking-wide mb-3">Vùng nguy hiểm</p>
                <form method="POST" action="{{ route('backend.kc-categories.destroy', $kcCategory) }}"
                      onsubmit="return confirm('Xác nhận xóa danh mục này?')">
                    @csrf
                    @method('DELETE')
                    <button type="submit" class="btn btn-error btn-sm btn-outline w-full gap-1.5">
                        <svg class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/>
                        </svg>
                        Xóa danh mục
                    </button>
                </form>
            </div>
        </div>
        @endcan

    </div>

</div>

@endsection
