@extends('layouts.backend')
@section('title', 'Mục tiêu KPI')


@section('content')
<div>
    <div class="flex flex-wrap items-center justify-between gap-3 mb-5">
        <div>
            <h1 class="text-2xl font-bold">Mục tiêu KPI</h1>
            <p class="text-sm text-base-content/50 mt-0.5">Thiết lập và theo dõi mục tiêu hiệu suất cá nhân</p>
        </div>
        <div class="flex gap-2">
            @can('viewLeaderboard', \Modules\KpiGoal\Models\KpiGoal::class)
            <a href="{{ route('backend.kpi.leaderboard') }}" class="btn btn-ghost btn-sm gap-1.5">
                Bảng xếp hạng
            </a>
            @endcan
            @can('create', \Modules\KpiGoal\Models\KpiGoal::class)
            <a href="{{ route('backend.kpi.goals.create') }}" class="btn btn-primary btn-sm gap-1.5">
                <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/>
                </svg>
                Thêm mục tiêu
            </a>
            @endcan
        </div>
    </div>

    @if(session('success'))
    <div class="alert alert-success mb-4"><span>{{ session('success') }}</span></div>
    @endif
    @if($errors->any())
    <div class="alert alert-error mb-4">
        <ul class="text-sm list-disc list-inside">@foreach($errors->all() as $e)<li>{{ $e }}</li>@endforeach</ul>
    </div>
    @endif

    {{-- Filters --}}
    <div class="card bg-base-100 shadow-sm border border-base-200 mb-4">
        <div class="card-body py-3">
            <form method="GET" class="flex flex-wrap gap-3 items-end">
                <div class="form-control">
                    <label class="label py-1"><span class="label-text text-xs">Nhân viên</span></label>
                    <select name="employee_id" class="select select-bordered select-sm w-48">
                        <option value="">Tất cả</option>
                        @foreach($employees as $emp)
                        <option value="{{ $emp->id }}" @selected(request('employee_id') == $emp->id)>
                            {{ $emp->full_name }} ({{ $emp->employee_code }})
                        </option>
                        @endforeach
                    </select>
                </div>
                <div class="form-control">
                    <label class="label py-1"><span class="label-text text-xs">Kỳ</span></label>
                    <select name="cycle_label" class="select select-bordered select-sm w-32">
                        <option value="">Tất cả</option>
                        @foreach($cycleLabels as $label)
                        <option value="{{ $label }}" @selected(request('cycle_label') === $label)>{{ $label }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="form-control">
                    <label class="label py-1"><span class="label-text text-xs">Trạng thái</span></label>
                    <select name="status" class="select select-bordered select-sm w-36">
                        <option value="">Tất cả</option>
                        @foreach($statuses as $s)
                        <option value="{{ $s['value'] }}" @selected(request('status') === $s['value'])>{{ $s['text'] }}</option>
                        @endforeach
                    </select>
                </div>
                <button type="submit" class="btn btn-sm btn-outline">Lọc</button>
                <a href="{{ route('backend.kpi.goals.index') }}" class="btn btn-sm btn-ghost">Xóa lọc</a>
            </form>
        </div>
    </div>

    <div class="card bg-base-100 shadow-sm border border-base-200">
        <div class="card-body p-0">
            <div class="overflow-x-auto">
                <table class="table table-zebra w-full">
                    <thead>
                        <tr class="text-xs text-base-content/60 uppercase">
                            <th>Mục tiêu</th>
                            <th>Nhân viên</th>
                            <th>Kỳ</th>
                            <th class="text-center">Tiến độ</th>
                            <th class="text-center">Đạt được</th>
                            <th class="text-center">Trọng số</th>
                            <th>Trạng thái</th>
                            <th class="text-right">Thao tác</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($goals as $goal)
                        <tr>
                            <td class="max-w-xs">
                                <div class="font-medium truncate text-sm" title="{{ $goal->title }}">{{ $goal->title }}</div>
                                @if($goal->unit)
                                <div class="text-xs text-base-content/50">{{ $goal->direction->label() }} · đơn vị: {{ $goal->unit }}</div>
                                @endif
                            </td>
                            <td>
                                <div class="text-sm">{{ $goal->employee?->full_name }}</div>
                                <div class="text-xs text-base-content/50">{{ $goal->employee?->employee_code }}</div>
                            </td>
                            <td class="text-sm font-mono">{{ $goal->cycle_label }}</td>
                            <td class="text-center">
                                <div class="text-sm tabular-nums">{{ $goal->current_value }} / {{ $goal->target_value }}</div>
                                <div class="w-24 mx-auto mt-1 bg-base-200 rounded-full h-1.5">
                                    <div class="bg-primary h-1.5 rounded-full"
                                         style="width: {{ min(100, (float)$goal->achievement_pct) }}%"></div>
                                </div>
                            </td>
                            <td class="text-center">
                                <span class="font-bold tabular-nums {{ (float)$goal->achievement_pct >= 100 ? 'text-success' : ((float)$goal->achievement_pct >= 70 ? 'text-warning' : 'text-error') }}">
                                    {{ number_format((float)$goal->achievement_pct, 1) }}%
                                </span>
                            </td>
                            <td class="text-center tabular-nums font-medium">{{ $goal->weight_percent }}%</td>
                            <td>
                                <span class="badge badge-sm {{ $goal->status->badgeClass() }}">
                                    {{ $goal->status->label() }}
                                </span>
                            </td>
                            <td class="text-right">
                                <a href="{{ route('backend.kpi.goals.show', $goal) }}" class="btn btn-ghost btn-xs">Xem</a>
                            </td>
                        </tr>
                        @empty
                        <tr>
                            <td colspan="8" class="text-center py-10 text-base-content/40">
                                Không có mục tiêu nào. <a href="{{ route('backend.kpi.goals.create') }}" class="link">Tạo mới</a>
                            </td>
                        </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
            @if($goals->hasPages())
            <div class="p-4 border-t border-base-200">{{ $goals->links() }}</div>
            @endif
        </div>
    </div>
</div>
@endsection
