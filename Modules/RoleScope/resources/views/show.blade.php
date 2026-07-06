@extends('layouts.backend')
@section('title', 'Chi tiết phân quyền')


@section('content')
<div>

{{-- Page header --}}
<div class="flex flex-wrap items-start justify-between gap-4 mb-6">
    <div class="flex items-center gap-4">
        <div class="w-14 h-14 rounded-xl bg-primary/10 flex items-center justify-center text-primary font-bold text-xl">
            {{ mb_substr($roleScope->user?->name ?? '?', 0, 1) }}
        </div>
        <div>
            <div class="flex items-center gap-2 flex-wrap">
                <h1 class="text-2xl font-bold text-base-content">{{ $roleScope->user?->name ?? '—' }}</h1>
                <span class="badge badge-outline">{{ $roleScope->role?->name ?? '—' }}</span>
                @if($roleScope->is_expired)
                    <span class="badge badge-error badge-sm">Đã hết hạn</span>
                @elseif($roleScope->expires_at)
                    <span class="badge badge-warning badge-sm">Có hạn</span>
                @else
                    <span class="badge badge-success badge-sm">Vĩnh viễn</span>
                @endif
            </div>
            <p class="text-sm text-base-content/50 mt-0.5">{{ $roleScope->user?->email ?? '—' }}</p>
        </div>
    </div>
    <div class="flex items-center gap-2">
        @can('update', $roleScope)
        <a href="{{ route('backend.role-scopes.edit', $roleScope) }}" class="btn btn-ghost btn-sm gap-1.5">
            <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"/>
            </svg>
            Chỉnh sửa
        </a>
        @endcan
        @can('delete', $roleScope)
        <form method="POST" action="{{ route('backend.role-scopes.destroy', $roleScope) }}"
              onsubmit="return confirm('Thu hồi quyền này không thể hoàn tác. Xác nhận?')">
            @csrf @method('DELETE')
            <button type="submit" class="btn btn-error btn-sm gap-1.5">
                <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/>
                </svg>
                Thu hồi
            </button>
        </form>
        @endcan
        <a href="{{ route('backend.role-scopes.index') }}" class="btn btn-ghost btn-sm gap-1.5">
            <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18"/>
            </svg>
            Quay lại
        </a>
    </div>
</div>

<div class="grid grid-cols-1 xl:grid-cols-[1fr_280px] gap-6">

    {{-- ── Details card ─────────────────────────────────────────────────── --}}
    <div class="space-y-4">
        <div class="card bg-base-100 shadow-sm border border-base-200">
            <div class="card-body">
                <h3 class="font-semibold mb-4">Thông tin phân quyền</h3>
                <div class="grid grid-cols-1 sm:grid-cols-2 gap-4 text-sm">
                    <div>
                        <p class="text-xs text-base-content/50 mb-0.5">User</p>
                        <p class="font-medium">{{ $roleScope->user?->name ?? '—' }}</p>
                        <p class="text-xs text-base-content/50">{{ $roleScope->user?->email ?? '' }}</p>
                    </div>
                    <div>
                        <p class="text-xs text-base-content/50 mb-0.5">Role</p>
                        <span class="badge badge-outline badge-sm">{{ $roleScope->role?->name ?? '—' }}</span>
                    </div>
                    <div>
                        <p class="text-xs text-base-content/50 mb-0.5">Phạm vi</p>
                        @if($roleScope->scope_level === 'org')
                            <span class="badge badge-info badge-sm">Toàn tổ chức</span>
                        @elseif($roleScope->scope_level === 'branch')
                            <div>
                                <span class="badge badge-accent badge-sm">Chi nhánh</span>
                                <p class="mt-1">{{ $roleScope->scopeBranch?->name ?? '—' }}
                                    @if($roleScope->scopeBranch?->code)
                                        <span class="badge badge-ghost badge-xs font-mono">{{ $roleScope->scopeBranch->code }}</span>
                                    @endif
                                </p>
                            </div>
                        @else
                            <div>
                                <span class="badge badge-secondary badge-sm">Phòng ban</span>
                                <p class="mt-1">{{ $roleScope->scopeDept?->name ?? '—' }}
                                    @if($roleScope->scopeDept?->code)
                                        <span class="badge badge-ghost badge-xs font-mono">{{ $roleScope->scopeDept->code }}</span>
                                    @endif
                                </p>
                                @if($roleScope->scopeBranch)
                                    <p class="text-xs text-base-content/50 mt-0.5">trong chi nhánh: {{ $roleScope->scopeBranch->name }}</p>
                                @endif
                            </div>
                        @endif
                    </div>
                    <div>
                        <p class="text-xs text-base-content/50 mb-0.5">Trạng thái</p>
                        @if($roleScope->is_expired)
                            <span class="badge badge-error">Đã hết hạn</span>
                        @elseif($roleScope->expires_at)
                            <span class="badge badge-warning">Có hạn đến {{ $roleScope->expires_at->format('d/m/Y H:i') }}</span>
                        @else
                            <span class="badge badge-success">Vĩnh viễn</span>
                        @endif
                    </div>
                </div>
            </div>
        </div>

        @if($roleScope->note)
        <div class="card bg-base-100 shadow-sm border border-base-200">
            <div class="card-body">
                <h3 class="font-semibold mb-2 text-sm">Lý do cấp</h3>
                <p class="text-sm text-base-content/80">{{ $roleScope->note }}</p>
            </div>
        </div>
        @endif
    </div>

    {{-- ── Sidebar ─────────────────────────────────────────────────────── --}}
    <div class="space-y-4">
        <div class="card bg-base-100 shadow-sm border border-base-200">
            <div class="card-body gap-3 text-sm">
                <h3 class="font-semibold">Audit</h3>
                <div>
                    <p class="text-xs text-base-content/50">Cấp bởi</p>
                    <p>{{ $roleScope->grantedByUser?->name ?? '—' }}</p>
                </div>
                <div>
                    <p class="text-xs text-base-content/50">Cấp lúc</p>
                    <p>{{ $roleScope->granted_at?->format('d/m/Y H:i') ?? '—' }}</p>
                </div>
                @if($roleScope->expires_at)
                <div>
                    <p class="text-xs text-base-content/50">Hết hạn</p>
                    <p class="{{ $roleScope->is_expired ? 'text-error' : 'text-warning' }}">
                        {{ $roleScope->expires_at->format('d/m/Y H:i') }}
                    </p>
                </div>
                @endif
                <div>
                    <p class="text-xs text-base-content/50">ID</p>
                    <p class="font-mono text-xs">#{{ $roleScope->id }}</p>
                </div>
            </div>
        </div>
    </div>
</div>

</div>
@endsection
