@extends('layouts.backend')
@section('title', $project->name)


@section('content')
{{-- ── Page header ──────────────────────────────────────────────────── --}}
<div class="flex flex-wrap items-start justify-between gap-4 mb-6">
    <div class="flex items-start gap-3">
        <div>
            <div class="flex items-center gap-2 mb-1">
                <h1 class="text-2xl font-bold text-base-content">{{ $project->name }}</h1>
                <span class="badge badge-sm badge-soft {{ $project->status->badgeClass() }}">
                    {{ $project->status->label() }}
                </span>
                <span class="badge badge-sm badge-soft {{ $project->priority->badgeClass() }}">
                    {{ $project->priority->label() }}
                </span>
            </div>
            <p class="text-sm font-mono text-base-content/40">{{ $project->code }}</p>
        </div>
    </div>

    <div class="flex gap-2">
        @can('update', $project)
        <a href="{{ route('backend.projects.edit', $project) }}" class="btn btn-warning btn-sm gap-1.5">
            <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"/>
            </svg>
            Chỉnh sửa
        </a>
        @endcan

        <a href="{{ route('backend.projects.index') }}" class="btn btn-ghost btn-sm">
            Danh sách dự án
        </a>
    </div>
</div>

<div class="grid grid-cols-1 xl:grid-cols-[1fr_320px] gap-6">

    {{-- ── Main column ────────────────────────────────────────────────── --}}
    <div class="space-y-5">

        {{-- Description --}}
        @if($project->description)
        <div class="card bg-base-100 shadow-sm border border-base-200">
            <div class="card-body">
                <h2 class="card-title text-base">Mô tả dự án</h2>
                <p class="text-sm text-base-content/70 whitespace-pre-wrap">{{ $project->description }}</p>
            </div>
        </div>
        @endif

        {{-- Timeline --}}
        <div class="card bg-base-100 shadow-sm border border-base-200">
            <div class="card-body">
                <h2 class="card-title text-base">Thời gian</h2>
                <div class="grid grid-cols-2 sm:grid-cols-3 gap-4">
                    <div>
                        <p class="text-xs text-base-content/40 uppercase tracking-wide mb-1">Bắt đầu</p>
                        <p class="text-sm font-medium">{{ $project->start_date?->format('d/m/Y') ?? '—' }}</p>
                    </div>
                    <div>
                        <p class="text-xs text-base-content/40 uppercase tracking-wide mb-1">Kết thúc</p>
                        <p class="text-sm font-medium">{{ $project->end_date?->format('d/m/Y') ?? '—' }}</p>
                    </div>
                    @if($project->completed_at)
                    <div>
                        <p class="text-xs text-base-content/40 uppercase tracking-wide mb-1">Hoàn thành</p>
                        <p class="text-sm font-medium text-success">{{ $project->completed_at->format('d/m/Y') }}</p>
                    </div>
                    @endif
                </div>
            </div>
        </div>

        {{-- Members --}}
        <div class="card bg-base-100 shadow-sm border border-base-200">
            <div class="card-body">
                <div class="flex items-center justify-between mb-4">
                    <h2 class="card-title text-base">Thành viên dự án</h2>
                    <span class="badge badge-ghost badge-sm">{{ $project->activeMembers->count() }} người</span>
                </div>

                @if($project->activeMembers->isEmpty())
                <div class="py-8 text-center opacity-40">
                    <svg class="w-10 h-10 mx-auto mb-2" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0z"/>
                    </svg>
                    <p class="text-sm">Chưa có thành viên nào</p>
                </div>
                @else
                <div class="overflow-x-auto">
                    <table class="table table-sm">
                        <thead>
                            <tr class="text-xs text-base-content/40">
                                <th>Nhân viên</th>
                                <th>Phòng ban</th>
                                <th>Vai trò</th>
                                <th>Đóng góp</th>
                                <th>Ngày tham gia</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($project->activeMembers as $member)
                            <tr>
                                <td>
                                    <div class="flex items-center gap-2">
                                        <div class="w-7 h-7 rounded-full bg-primary/10 flex items-center justify-center text-primary text-xs font-bold shrink-0">
                                            {{ mb_substr($member->employee?->full_name ?? '?', 0, 1) }}
                                        </div>
                                        <div>
                                            <p class="text-sm font-medium">{{ $member->employee?->full_name ?? '—' }}</p>
                                            @if($member->is_lead)
                                            <span class="badge badge-xs badge-warning">Trưởng nhóm</span>
                                            @endif
                                        </div>
                                    </div>
                                </td>
                                <td class="text-xs text-base-content/60">{{ $member->employee?->department?->name ?? '—' }}</td>
                                <td>
                                    <span class="badge badge-xs badge-ghost">{{ $member->role->label() }}</span>
                                </td>
                                <td class="text-xs">
                                    {{ $member->contribution_pct !== null ? $member->contribution_pct . '%' : '—' }}
                                </td>
                                <td class="text-xs text-base-content/60">
                                    {{ $member->joined_at?->format('d/m/Y') ?? '—' }}
                                </td>
                            </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
                @endif
            </div>
        </div>

    </div>

    {{-- ── Side column ─────────────────────────────────────────────────── --}}
    <div class="space-y-4">

        {{-- Project info --}}
        <div class="card bg-base-100 shadow-sm border border-base-200">
            <div class="card-body gap-3">
                <h2 class="card-title text-base">Thông tin dự án</h2>

                <div class="space-y-2 text-sm">
                    <div class="flex justify-between items-start gap-2">
                        <span class="text-base-content/50 shrink-0">Người phụ trách</span>
                        <span class="font-medium text-right">{{ $project->owner?->full_name ?? '—' }}</span>
                    </div>
                    <div class="flex justify-between items-start gap-2">
                        <span class="text-base-content/50 shrink-0">Chi nhánh</span>
                        <span class="text-right">{{ $project->branch?->name ?? '—' }}</span>
                    </div>
                    <div class="flex justify-between items-start gap-2">
                        <span class="text-base-content/50 shrink-0">Phòng ban</span>
                        <span class="text-right">{{ $project->department?->name ?? '—' }}</span>
                    </div>
                    @if($project->category)
                    <div class="flex justify-between items-start gap-2">
                        <span class="text-base-content/50 shrink-0">Phân loại</span>
                        <span class="badge badge-xs badge-ghost">{{ $project->category }}</span>
                    </div>
                    @endif
                </div>
            </div>
        </div>

        {{-- Budget --}}
        @if($project->budget)
        <div class="card bg-base-100 shadow-sm border border-base-200">
            <div class="card-body gap-2">
                <h2 class="card-title text-base">Ngân sách</h2>
                <p class="text-2xl font-bold text-primary">
                    {{ number_format($project->budget, 0, ',', '.') }}
                    <span class="text-sm font-normal text-base-content/40">{{ $project->currency }}</span>
                </p>
            </div>
        </div>
        @endif

        {{-- System info --}}
        <div class="card bg-base-100 shadow-sm border border-base-200">
            <div class="card-body gap-2">
                <h2 class="card-title text-sm text-base-content/50">Thông tin hệ thống</h2>
                <div class="text-xs text-base-content/50 space-y-1">
                    <p>Tạo: {{ $project->created_at?->format('d/m/Y H:i') }}</p>
                    @if($project->createdBy)
                    <p>Bởi: {{ $project->createdBy->name }}</p>
                    @endif
                    <p>Cập nhật: {{ $project->updated_at?->format('d/m/Y H:i') }}</p>
                    @if($project->updatedBy)
                    <p>Bởi: {{ $project->updatedBy->name }}</p>
                    @endif
                </div>
            </div>
        </div>

        @can('delete', $project)
        <div class="card bg-error/5 border border-error/20">
            <div class="card-body gap-2">
                <h2 class="card-title text-sm text-error">Vùng nguy hiểm</h2>
                <p class="text-xs text-base-content/50">Xóa dự án sẽ xóa toàn bộ dữ liệu liên quan (thành viên).</p>
                <form method="POST" action="{{ route('backend.projects.destroy', $project) }}"
                      onsubmit="return confirm('Bạn chắc chắn muốn xóa dự án này?')">
                    @csrf
                    @method('DELETE')
                    <button type="submit" class="btn btn-error btn-sm btn-outline w-full gap-1.5 mt-1">
                        <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/>
                        </svg>
                        Xóa dự án
                    </button>
                </form>
            </div>
        </div>
        @endcan

    </div>

</div>
@endsection
