@extends('layouts.backend')
@section('title', $organization->name)


@section('content')

{{-- ── Page header ──────────────────────────────────────────────────────── --}}
<div class="flex flex-wrap items-start justify-between gap-4 mb-6">
    <div class="flex items-center gap-4 min-w-0">

        {{-- Avatar --}}
        <div class="w-12 h-12 rounded-xl bg-primary/10 flex items-center justify-center text-primary font-bold text-lg shrink-0 select-none">
            {{ mb_strtoupper(mb_substr($organization->name, 0, 1)) }}
        </div>

        {{-- Identity --}}
        <div class="min-w-0">
            <h1 class="text-2xl font-bold text-base-content leading-tight truncate">{{ $organization->name }}</h1>
            <div class="flex flex-wrap items-center gap-2 mt-1">
                @if ($organization->status->value === 'active')
                    <span class="badge badge-success badge-sm">Hoạt động</span>
                @elseif ($organization->status->value === 'suspended')
                    <span class="badge badge-error badge-sm">Tạm khóa</span>
                @else
                    <span class="badge badge-ghost badge-sm">Không hoạt động</span>
                @endif
                @if ($organization->industry)
                    <span class="text-sm text-base-content/50">{{ $organization->industry }}</span>
                @endif
                @if ($organization->tax_code)
                    <span class="text-xs text-base-content/40 font-mono">MST: {{ $organization->tax_code }}</span>
                @endif
            </div>
        </div>

    </div>

    {{-- Actions --}}
    <div class="flex items-center gap-2 shrink-0">
        <a href="{{ route('backend.users.index', ['org' => $organization->id]) }}"
           class="btn btn-ghost btn-sm gap-2">
            <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0z"/>
            </svg>
            Tài khoản
            <span class="badge badge-ghost badge-sm">{{ $organization->members_count }}</span>
        </a>
        @can('update', $organization)
        <a href="{{ route('backend.organizations.edit', $organization) }}" class="btn btn-primary btn-sm gap-2">
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

    {{-- ── Cột chính ──────────────────────────────────────────────────── --}}
    <div class="space-y-5">

        {{-- Thông tin liên hệ --}}
        <div class="card bg-base-100 shadow-sm border border-base-200">
            <div class="card-body">

                <h2 class="card-title text-base mb-5">
                    <svg class="w-4 h-4 text-primary" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 8l7.89 5.26a2 2 0 002.22 0L21 8M5 19h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z"/>
                    </svg>
                    Thông tin liên hệ
                </h2>

                <dl class="grid grid-cols-1 sm:grid-cols-2 gap-x-8 gap-y-4 text-sm">

                    <div>
                        <dt class="text-xs font-semibold text-base-content/40 uppercase tracking-wide mb-1">Điện thoại</dt>
                        <dd class="font-medium">
                            @if($organization->phone)
                                <a href="tel:{{ $organization->phone }}" class="hover:text-primary transition-colors">{{ $organization->phone }}</a>
                            @else
                                <span class="text-base-content/30 font-normal">Chưa cập nhật</span>
                            @endif
                        </dd>
                    </div>

                    <div>
                        <dt class="text-xs font-semibold text-base-content/40 uppercase tracking-wide mb-1">Email</dt>
                        <dd class="font-medium">
                            @if($organization->email)
                                <a href="mailto:{{ $organization->email }}" class="link link-primary font-normal no-underline hover:underline">{{ $organization->email }}</a>
                            @else
                                <span class="text-base-content/30 font-normal">Chưa cập nhật</span>
                            @endif
                        </dd>
                    </div>

                    @if($organization->website)
                    <div class="sm:col-span-2">
                        <dt class="text-xs font-semibold text-base-content/40 uppercase tracking-wide mb-1">Website</dt>
                        <dd>
                            <a href="{{ $organization->website }}" target="_blank" rel="noopener"
                               class="link link-primary no-underline hover:underline break-all text-sm">{{ $organization->website }}</a>
                        </dd>
                    </div>
                    @endif

                </dl>

            </div>
        </div>

        {{-- Địa chỉ (chỉ render nếu có) --}}
        @if($organization->full_address || $organization->address || $organization->province)
        <div class="card bg-base-100 shadow-sm border border-base-200">
            <div class="card-body">

                <h2 class="card-title text-base mb-5">
                    <svg class="w-4 h-4 text-primary" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z"/>
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 11a3 3 0 11-6 0 3 3 0 016 0z"/>
                    </svg>
                    Địa chỉ
                </h2>

                <dl class="text-sm">
                    <dt class="text-xs font-semibold text-base-content/40 uppercase tracking-wide mb-1">Địa chỉ đầy đủ</dt>
                    <dd class="font-medium">
                        @if ($organization->full_address)
                            {{ $organization->full_address }}{{ $organization->country ? ' (' . $organization->country . ')' : '' }}
                        @else
                            {{ implode(', ', array_filter([$organization->address, $organization->ward?->name, $organization->province?->name])) }}{{ $organization->country ? ' (' . $organization->country . ')' : '' }}
                        @endif
                    </dd>
                </dl>

            </div>
        </div>
        @endif

        {{-- Mô tả (chỉ render nếu có) --}}
        @if ($organization->description)
        <div class="card bg-base-100 shadow-sm border border-base-200">
            <div class="card-body">

                <h2 class="card-title text-base mb-5">
                    <svg class="w-4 h-4 text-primary" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/>
                    </svg>
                    Mô tả
                </h2>

                <div class="text-sm text-base-content/80 rich-content leading-relaxed">
                    {!! sanitize_rich_text($organization->description) !!}
                </div>

            </div>
        </div>
        @endif

    </div>{{-- /cột chính --}}

    {{-- ── Sidebar ──────────────────────────────────────────────────────── --}}
    <div class="space-y-4">

        {{-- Subscription info --}}
        @can(\App\Enums\PermissionEnum::SUBSCRIPTION_VIEW->value)
        @php
            $orgSub = $organization->planSubscription('main');
        @endphp
        <div class="card bg-base-100 shadow-sm border border-base-200">
            <div class="card-body">
                <h3 class="font-semibold text-sm mb-3 flex items-center gap-2">
                    <svg class="w-4 h-4 text-primary" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 10h18M7 15h1m4 0h1m-7 4h12a3 3 0 003-3V8a3 3 0 00-3-3H6a3 3 0 00-3 3v8a3 3 0 003 3z"/>
                    </svg>
                    Subscription
                </h3>
                @if ($orgSub)
                <dl class="space-y-2 text-sm">
                    <div class="flex items-center justify-between gap-2">
                        <dt class="text-base-content/50 shrink-0">Plan</dt>
                        <dd class="font-semibold">{{ $orgSub->plan?->name ?? '—' }}</dd>
                    </div>
                    <div class="flex items-center justify-between gap-2">
                        <dt class="text-base-content/50 shrink-0">Trạng thái</dt>
                        <dd>
                            @if ($orgSub->onTrial())
                                <span class="badge badge-info badge-xs">Trial</span>
                            @elseif ($orgSub->active())
                                <span class="badge badge-success badge-xs">Active</span>
                            @elseif ($orgSub->canceled())
                                <span class="badge badge-warning badge-xs">Canceled</span>
                            @else
                                <span class="badge badge-error badge-xs">Expired</span>
                            @endif
                        </dd>
                    </div>
                    @if ($orgSub->ends_at)
                    <div class="flex items-center justify-between gap-2">
                        <dt class="text-base-content/50 shrink-0">Hết hạn</dt>
                        <dd class="text-xs">{{ $orgSub->ends_at->format('d/m/Y') }}</dd>
                    </div>
                    @endif
                </dl>
                @can(\App\Enums\PermissionEnum::SUBSCRIPTION_ADMIN->value)
                <div class="mt-3">
                    <a href="{{ route('subscription.admin.subscriptions.index', ['search' => $organization->name]) }}"
                       class="btn btn-ghost btn-xs w-full">Quản lý</a>
                </div>
                @endcan
                @else
                <p class="text-xs text-base-content/40">Chưa có subscription.</p>
                @can(\App\Enums\PermissionEnum::SUBSCRIPTION_ADMIN->value)
                <div class="mt-2">
                    <a href="{{ route('subscription.admin.subscriptions.index', ['search' => $organization->name]) }}"
                       class="btn btn-ghost btn-xs w-full">Gán plan</a>
                </div>
                @endcan
                @endif
            </div>
        </div>
        @endcan

        {{-- Thông tin hệ thống --}}
        <div class="card bg-base-100 shadow-sm border border-base-200">
            <div class="card-body">

                <h3 class="font-semibold text-sm mb-4">Thông tin hệ thống</h3>

                <dl class="space-y-3 text-sm">
                    <div class="flex items-center justify-between gap-4">
                        <dt class="text-base-content/50 shrink-0">Trạng thái</dt>
                        <dd>
                            @if ($organization->status->value === 'active')
                                <span class="badge badge-success badge-sm">Hoạt động</span>
                            @elseif ($organization->status->value === 'suspended')
                                <span class="badge badge-error badge-sm">Tạm khóa</span>
                            @else
                                <span class="badge badge-ghost badge-sm">Không hoạt động</span>
                            @endif
                        </dd>
                    </div>
                    <div class="flex items-center justify-between gap-4">
                        <dt class="text-base-content/50 shrink-0">Thành viên</dt>
                        <dd class="font-semibold">{{ number_format($organization->members_count) }}</dd>
                    </div>
                    <div class="flex items-start justify-between gap-4">
                        <dt class="text-base-content/50 shrink-0">Slug</dt>
                        <dd class="font-mono text-xs text-right break-all">{{ $organization->slug }}</dd>
                    </div>
                    <div class="flex items-center justify-between gap-4">
                        <dt class="text-base-content/50 shrink-0">Ngày tạo</dt>
                        <dd class="text-right">{{ $organization->created_at->format('d/m/Y') }}</dd>
                    </div>
                    <div class="flex items-center justify-between gap-4">
                        <dt class="text-base-content/50 shrink-0">Cập nhật</dt>
                        <dd class="text-right text-base-content/60">{{ $organization->updated_at->diffForHumans() }}</dd>
                    </div>
                </dl>

            </div>
        </div>

        {{-- Thao tác --}}
        <div class="card bg-base-100 shadow-sm border border-base-200">
            <div class="card-body p-2">

                <a href="{{ route('backend.users.index', ['org' => $organization->id]) }}"
                   class="flex items-center justify-between gap-2 rounded-xl px-3 py-2.5 hover:bg-base-200/70 transition-colors group">
                    <div class="flex items-center gap-2.5">
                        <svg class="w-4 h-4 text-base-content/40 group-hover:text-primary transition-colors shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0z"/>
                        </svg>
                        <span class="text-sm font-medium">Danh sách thành viên</span>
                    </div>
                    <svg class="w-3.5 h-3.5 text-base-content/30 shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/>
                    </svg>
                </a>

                @can('update', $organization)
                <a href="{{ route('backend.organizations.verticals.index', $organization) }}"
                   class="flex items-center justify-between gap-2 rounded-xl px-3 py-2.5 hover:bg-base-200/70 transition-colors group">
                    <div class="flex items-center gap-2.5">
                        <svg class="w-4 h-4 text-base-content/40 group-hover:text-primary transition-colors shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 10V3L4 14h7v7l9-11h-7z"/>
                        </svg>
                        <span class="text-sm font-medium">Dịch vụ triển khai</span>
                    </div>
                    <svg class="w-3.5 h-3.5 text-base-content/30 shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/>
                    </svg>
                </a>
                @endcan

                @can('update', $organization)
                <a href="{{ route('backend.organizations.edit', $organization) }}"
                   class="flex items-center justify-between gap-2 rounded-xl px-3 py-2.5 hover:bg-base-200/70 transition-colors group">
                    <div class="flex items-center gap-2.5">
                        <svg class="w-4 h-4 text-base-content/40 group-hover:text-primary transition-colors shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"/>
                        </svg>
                        <span class="text-sm font-medium">Chỉnh sửa thông tin</span>
                    </div>
                    <svg class="w-3.5 h-3.5 text-base-content/30 shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/>
                    </svg>
                </a>
                @endcan

                <a href="{{ route('backend.organizations.index') }}"
                   class="flex items-center justify-between gap-2 rounded-xl px-3 py-2.5 hover:bg-base-200/70 transition-colors group">
                    <div class="flex items-center gap-2.5">
                        <svg class="w-4 h-4 text-base-content/40 group-hover:text-primary transition-colors shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 10h16M4 14h16M4 18h16"/>
                        </svg>
                        <span class="text-sm font-medium">Tất cả tổ chức</span>
                    </div>
                    <svg class="w-3.5 h-3.5 text-base-content/30 shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/>
                    </svg>
                </a>

            </div>
        </div>

    </div>{{-- /sidebar --}}

</div>{{-- /grid --}}

{{-- ── Thành viên gần đây ────────────────────────────────────────────────── --}}
@if ($members->isNotEmpty())
<div class="card bg-base-100 shadow-sm border border-base-200 mt-5">
    <div class="card-body p-0">

        <div class="flex items-center justify-between px-5 py-4 border-b border-base-200">
            <h2 class="font-semibold text-sm flex items-center gap-2">
                <svg class="w-4 h-4 text-base-content/50" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0z"/>
                </svg>
                Thành viên gần đây
            </h2>
            <a href="{{ route('backend.users.index', ['org' => $organization->id]) }}"
               class="btn btn-ghost btn-xs gap-1">
                Xem tất cả
                <svg class="w-3 h-3" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/>
                </svg>
            </a>
        </div>

        <div class="overflow-x-auto">
            <table class="table table-sm">
                <thead>
                    <tr class="text-xs text-base-content/40 uppercase tracking-wide">
                        <th>Thành viên</th>
                        <th>Phòng ban</th>
                        <th>Vai trò</th>
                        <th>Ngày tham gia</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach ($members as $m)
                    <tr class="hover">
                        <td>
                            <div class="flex items-center gap-2.5">
                                <div class="w-7 h-7 rounded-full bg-base-300 flex items-center justify-center text-xs font-semibold shrink-0">
                                    {{ mb_strtoupper(mb_substr($m->user?->name ?? '?', 0, 1)) }}
                                </div>
                                <div>
                                    <p class="font-medium text-sm leading-tight">{{ $m->user?->name ?? '—' }}</p>
                                    <p class="text-xs text-base-content/50 leading-tight">{{ $m->user?->email ?? '' }}</p>
                                </div>
                            </div>
                        </td>
                        <td class="text-sm text-base-content/70">{{ $m->user?->department ?? '—' }}</td>
                        <td><span class="badge badge-ghost badge-sm">{{ $m->role }}</span></td>
                        <td class="text-sm text-base-content/50">{{ $m->joined_at?->format('d/m/Y') ?? '—' }}</td>
                    </tr>
                    @endforeach
                </tbody>
            </table>
        </div>

    </div>
</div>
@endif

@endsection
