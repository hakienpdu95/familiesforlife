@extends('layouts.backend')
@section('title', 'Chi nhánh: ' . $branch->name)


@section('content')

{{-- ── Page header ──────────────────────────────────────────────────────── --}}
<div class="flex flex-wrap items-start justify-between gap-4 mb-6">
    <div class="flex items-center gap-4">
        <div class="w-12 h-12 rounded-xl bg-primary/10 flex items-center justify-center">
            <svg class="w-6 h-6 text-primary" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4"/>
            </svg>
        </div>
        <div>
            <div class="flex items-center gap-2">
                <h1 class="text-2xl font-bold text-base-content">{{ $branch->name }}</h1>
                <span class="badge badge-sm badge-soft {{ $branch->status->badgeClass() }}">{{ $branch->status->label() }}</span>
            </div>
            <div class="flex items-center gap-2 mt-0.5">
                <span class="text-sm text-base-content/50 font-mono">{{ $branch->code }}</span>
                <span class="text-base-content/30">·</span>
                <span class="badge badge-sm badge-soft {{ $branch->type->badgeClass() }}">{{ $branch->type->label() }}</span>
                @if($branch->depth > 0)
                <span class="text-base-content/30">·</span>
                <span class="text-xs text-base-content/50">Cấp {{ $branch->depth }}</span>
                @endif
            </div>
        </div>
    </div>
    <div class="flex gap-2">
        <a href="{{ route('backend.branches.index') }}" class="btn btn-ghost btn-sm">
            <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18"/>
            </svg>
            Danh sách
        </a>
        @can('update', $branch)
        <a href="{{ route('backend.branches.edit', $branch) }}" class="btn btn-primary btn-sm gap-1.5">
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
                        <dt class="text-base-content/50 text-xs">Tên chi nhánh</dt>
                        <dd class="font-medium mt-0.5">{{ $branch->name }}</dd>
                    </div>
                    <div>
                        <dt class="text-base-content/50 text-xs">Mã chi nhánh</dt>
                        <dd class="font-mono font-medium mt-0.5">{{ $branch->code }}</dd>
                    </div>
                    <div>
                        <dt class="text-base-content/50 text-xs">Loại</dt>
                        <dd class="mt-0.5">
                            <span class="badge badge-sm badge-soft {{ $branch->type->badgeClass() }}">{{ $branch->type->label() }}</span>
                        </dd>
                    </div>
                    <div>
                        <dt class="text-base-content/50 text-xs">Trạng thái</dt>
                        <dd class="mt-0.5">
                            <span class="badge badge-sm badge-soft {{ $branch->status->badgeClass() }}">{{ $branch->status->label() }}</span>
                        </dd>
                    </div>
                    @if($branch->parent)
                    <div>
                        <dt class="text-base-content/50 text-xs">Chi nhánh cha</dt>
                        <dd class="mt-0.5">
                            <a href="{{ route('backend.branches.show', $branch->parent) }}" class="link link-primary text-sm">
                                {{ $branch->parent->name }}
                            </a>
                            <span class="text-base-content/40 font-mono text-xs ml-1">({{ $branch->parent->code }})</span>
                        </dd>
                    </div>
                    @endif
                    <div>
                        <dt class="text-base-content/50 text-xs">Cấp trong cây</dt>
                        <dd class="font-medium mt-0.5">{{ $branch->depth }} ({{ ['Trụ sở', 'Cấp 1', 'Cấp 2'][$branch->depth] ?? 'N/A' }})</dd>
                    </div>
                    @if($branch->tax_code)
                    <div>
                        <dt class="text-base-content/50 text-xs">Mã số thuế</dt>
                        <dd class="font-mono font-medium mt-0.5">{{ $branch->tax_code }}</dd>
                    </div>
                    @endif
                    @if($branch->opened_at)
                    <div>
                        <dt class="text-base-content/50 text-xs">Ngày khai trương</dt>
                        <dd class="mt-0.5">{{ $branch->opened_at->format('d/m/Y') }}</dd>
                    </div>
                    @endif
                    @if($branch->closed_at)
                    <div>
                        <dt class="text-base-content/50 text-xs">Ngày đóng cửa</dt>
                        <dd class="mt-0.5">{{ $branch->closed_at->format('d/m/Y') }}</dd>
                    </div>
                    @endif
                </dl>
            </div>
        </div>

        {{-- Liên hệ --}}
        @if($branch->phone || $branch->email || $branch->fax)
        <div class="card bg-base-100 shadow-sm border border-base-200">
            <div class="card-body">
                <h2 class="font-semibold mb-3">Liên hệ</h2>
                <dl class="grid grid-cols-1 sm:grid-cols-2 gap-x-6 gap-y-3 text-sm">
                    @if($branch->phone)
                    <div>
                        <dt class="text-base-content/50 text-xs">Điện thoại</dt>
                        <dd class="font-medium mt-0.5">
                            <a href="tel:{{ $branch->phone }}" class="link link-primary">{{ $branch->phone }}</a>
                        </dd>
                    </div>
                    @endif
                    @if($branch->email)
                    <div>
                        <dt class="text-base-content/50 text-xs">Email</dt>
                        <dd class="font-medium mt-0.5">
                            <a href="mailto:{{ $branch->email }}" class="link link-primary">{{ $branch->email }}</a>
                        </dd>
                    </div>
                    @endif
                    @if($branch->fax)
                    <div>
                        <dt class="text-base-content/50 text-xs">Fax</dt>
                        <dd class="font-medium mt-0.5">{{ $branch->fax }}</dd>
                    </div>
                    @endif
                </dl>
            </div>
        </div>
        @endif

        {{-- Địa chỉ --}}
        @if($branch->province || $branch->address)
        <div class="card bg-base-100 shadow-sm border border-base-200">
            <div class="card-body">
                <h2 class="font-semibold mb-3">Địa chỉ</h2>
                <dl class="grid grid-cols-1 sm:grid-cols-2 gap-x-6 gap-y-3 text-sm">
                    @if($branch->province)
                    <div>
                        <dt class="text-base-content/50 text-xs">Tỉnh / Thành phố</dt>
                        <dd class="font-medium mt-0.5">{{ $branch->province->name }}</dd>
                    </div>
                    @endif
                    @if($branch->ward)
                    <div>
                        <dt class="text-base-content/50 text-xs">Phường / Xã</dt>
                        <dd class="font-medium mt-0.5">{{ $branch->ward->name }}</dd>
                    </div>
                    @endif
                    @if($branch->address)
                    <div class="sm:col-span-2">
                        <dt class="text-base-content/50 text-xs">Địa chỉ chi tiết</dt>
                        <dd class="font-medium mt-0.5">{{ $branch->address }}</dd>
                    </div>
                    @endif
                    @if($branch->lat && $branch->lng)
                    <div>
                        <dt class="text-base-content/50 text-xs">Toạ độ GPS</dt>
                        <dd class="font-mono text-xs mt-0.5">{{ $branch->lat }}, {{ $branch->lng }}</dd>
                    </div>
                    @endif
                </dl>
            </div>
        </div>
        @endif

        {{-- Chi nhánh con --}}
        @if($branch->children->count() > 0)
        <div class="card bg-base-100 shadow-sm border border-base-200">
            <div class="card-body">
                <h2 class="font-semibold mb-3">Chi nhánh con ({{ $branch->children->count() }})</h2>
                <div class="space-y-2">
                    @foreach($branch->children as $child)
                    <a href="{{ route('backend.branches.show', $child) }}"
                       class="flex items-center justify-between p-3 rounded-lg bg-base-200/50 hover:bg-base-200 transition-colors">
                        <div class="flex items-center gap-3">
                            <span class="badge badge-sm badge-soft {{ $child->type->badgeClass() }}">{{ $child->type->label() }}</span>
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

        {{-- Settings --}}
        <div class="card bg-base-100 shadow-sm border border-base-200">
            <div class="card-body gap-3 text-sm">
                <h3 class="font-semibold">Cài đặt</h3>
                <div class="space-y-2 text-xs">
                    <div class="flex justify-between">
                        <span class="text-base-content/50">Múi giờ</span>
                        <span class="font-mono">{{ $branch->timezone ?? 'Kế thừa org' }}</span>
                    </div>
                    <div class="flex justify-between">
                        <span class="text-base-content/50">Tiền tệ</span>
                        <span class="font-mono">{{ $branch->currency ?? 'Kế thừa org' }}</span>
                    </div>
                </div>
            </div>
        </div>

        {{-- Audit info --}}
        <div class="card bg-base-100 shadow-sm border border-base-200">
            <div class="card-body gap-3 text-xs text-base-content/60">
                <h3 class="font-semibold text-sm text-base-content">Audit</h3>
                @if($branch->createdBy)
                <div>
                    <span class="opacity-70">Tạo bởi:</span>
                    <span class="font-medium text-base-content ml-1">{{ $branch->createdBy->name }}</span>
                </div>
                @endif
                <div>
                    <span class="opacity-70">Ngày tạo:</span>
                    <span class="font-medium text-base-content ml-1">{{ $branch->created_at?->format('d/m/Y H:i') }}</span>
                </div>
                @if($branch->updatedBy)
                <div>
                    <span class="opacity-70">Sửa bởi:</span>
                    <span class="font-medium text-base-content ml-1">{{ $branch->updatedBy->name }}</span>
                </div>
                @endif
                <div>
                    <span class="opacity-70">Cập nhật:</span>
                    <span class="font-medium text-base-content ml-1">{{ $branch->updated_at?->format('d/m/Y H:i') }}</span>
                </div>
                <div class="pt-1 border-t border-base-200">
                    <span class="opacity-70">UUID:</span>
                    <p class="font-mono text-[10px] break-all mt-0.5 text-base-content/40">{{ $branch->uuid }}</p>
                </div>
            </div>
        </div>

        {{-- Danger zone --}}
        @can('delete', $branch)
        @if($branch->children->count() === 0 && $branch->status->value !== 'active')
        <div class="card bg-base-100 shadow-sm border border-error/30">
            <div class="card-body gap-3">
                <h3 class="font-semibold text-sm text-error">Khu vực nguy hiểm</h3>
                <form method="POST" action="{{ route('backend.branches.destroy', $branch) }}"
                      onsubmit="return confirm('Bạn chắc chắn muốn xóa chi nhánh này?')">
                    @csrf
                    @method('DELETE')
                    <button type="submit" class="btn btn-error btn-sm btn-outline w-full gap-1.5">
                        <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/>
                        </svg>
                        Xóa chi nhánh
                    </button>
                </form>
            </div>
        </div>
        @endif
        @endcan

    </div>

</div>
@endsection
