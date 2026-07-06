@extends('layouts.backend')
@section('title', 'Bảng xếp hạng KPI')


@section('content')
<div>
    <div class="flex flex-wrap items-center justify-between gap-3 mb-5">
        <div>
            <h1 class="text-2xl font-bold">Bảng xếp hạng KPI</h1>
            <p class="text-sm text-base-content/50 mt-0.5">Điểm tổng hợp theo kỳ đã chốt</p>
        </div>

        @can('closeCycle', \Modules\KpiGoal\Models\KpiGoal::class)
        <div x-data="{ open: false }">
            <button @click="open = true" class="btn btn-warning btn-sm">Chốt kỳ</button>
            <div x-show="open" class="fixed inset-0 bg-black/40 z-50 flex items-center justify-center" x-cloak>
                <div class="card bg-base-100 w-96 shadow-xl">
                    <div class="card-body">
                        <h3 class="card-title text-base">Chốt kỳ KPI</h3>
                        <p class="text-sm text-base-content/60">Tạo snapshot bất biến cho tất cả mục tiêu đang theo dõi.</p>
                        <form method="POST" action="{{ route('backend.kpi.cycles.close') }}" class="space-y-3 mt-2">
                            @csrf
                            <div class="form-control">
                                <label class="label"><span class="label-text text-sm">Kỳ</span></label>
                                <input type="text" name="cycle_label" placeholder="Q3-2024"
                                       class="input input-bordered input-sm" required>
                            </div>
                            <div class="form-control">
                                <label class="label"><span class="label-text text-sm">Employee ID</span></label>
                                <input type="number" name="employee_id"
                                       class="input input-bordered input-sm" required>
                                <span class="label-text-alt">Nhập ID nhân viên cần chốt</span>
                            </div>
                            <div class="flex gap-2 justify-end">
                                <button type="button" @click="open = false" class="btn btn-ghost btn-sm">Hủy</button>
                                <button type="submit" class="btn btn-warning btn-sm">Xác nhận chốt kỳ</button>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        </div>
        @endcan
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
                    <label class="label py-1"><span class="label-text text-xs">Kỳ</span></label>
                    <select name="cycle_label" class="select select-bordered select-sm w-32" onchange="this.form.submit()">
                        @foreach($cycleLabels as $label)
                        <option value="{{ $label }}" @selected($label === $cycleLabel)>{{ $label }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="form-control">
                    <label class="label py-1"><span class="label-text text-xs">Phòng ban</span></label>
                    <select name="department_id" class="select select-bordered select-sm w-40" onchange="this.form.submit()">
                        <option value="">Tất cả</option>
                        @foreach($departments as $dept)
                        <option value="{{ $dept->id }}" @selected($dept->id === $departmentId)>{{ $dept->name }}</option>
                        @endforeach
                    </select>
                </div>
            </form>
        </div>
    </div>

    @if($rows->isEmpty())
    <div class="card bg-base-100 border border-base-200">
        <div class="card-body text-center py-12 text-base-content/40">
            @if($cycleLabel)
                Chưa có snapshot cho kỳ <strong>{{ $cycleLabel }}</strong>. Cần chốt kỳ trước.
            @else
                Chưa có dữ liệu.
            @endif
        </div>
    </div>
    @else
    <div class="card bg-base-100 shadow-sm border border-base-200">
        <div class="card-body p-0">
            <div class="overflow-x-auto">
                <table class="table w-full">
                    <thead>
                        <tr class="text-xs text-base-content/60 uppercase">
                            <th class="text-center w-12">#</th>
                            <th>Nhân viên</th>
                            <th>Phòng ban</th>
                            <th class="text-center">Số mục tiêu</th>
                            <th class="text-center">Điểm thô (/100)</th>
                            <th class="text-center">Điểm KPI (/5)</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($rows as $rank => $row)
                        <tr class="{{ $rank === 0 ? 'bg-yellow-50' : ($rank === 1 ? 'bg-gray-50' : ($rank === 2 ? 'bg-orange-50' : '')) }}">
                            <td class="text-center font-bold text-lg">
                                @if($rank === 0) 🥇
                                @elseif($rank === 1) 🥈
                                @elseif($rank === 2) 🥉
                                @else {{ $rank + 1 }}
                                @endif
                            </td>
                            <td>
                                <div class="font-medium">{{ $row->full_name }}</div>
                                <div class="text-xs text-base-content/50">{{ $row->employee_code }}</div>
                            </td>
                            <td class="text-sm">{{ $row->department_name }}</td>
                            <td class="text-center tabular-nums">{{ $row->goal_count }}</td>
                            <td class="text-center">
                                <div class="font-bold text-lg tabular-nums
                                    {{ (float)$row->kpi_raw_score >= 90 ? 'text-success' : ((float)$row->kpi_raw_score >= 70 ? 'text-warning' : 'text-error') }}">
                                    {{ number_format((float)$row->kpi_raw_score, 1) }}
                                </div>
                                <div class="w-24 mx-auto mt-1 bg-base-200 rounded-full h-1.5">
                                    <div class="bg-primary h-1.5 rounded-full"
                                         style="width: {{ min(100, (float)$row->kpi_raw_score) }}%"></div>
                                </div>
                            </td>
                            <td class="text-center">
                                <span class="badge badge-lg font-bold
                                    {{ (float)$row->kpi_score_5 >= 4.5 ? 'badge-success' : ((float)$row->kpi_score_5 >= 3.5 ? 'badge-info' : ((float)$row->kpi_score_5 >= 2.5 ? 'badge-warning' : 'badge-error')) }}">
                                    {{ number_format((float)$row->kpi_score_5, 2) }} / 5.0
                                </span>
                            </td>
                        </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </div>
    </div>
    @endif
</div>
@endsection
