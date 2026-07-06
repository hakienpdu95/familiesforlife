@extends('layouts.backend')
@section('title', 'Skill Gap — Năng lực số')

@section('content')

<div class="flex flex-wrap items-center justify-between gap-3 mb-5">
    <div>
        <div class="text-xs breadcrumbs text-base-content/40 mb-1">
            <ul><li><a href="{{ route('report.competency.index') }}">Năng lực số</a></li><li>Skill Gap</li></ul>
        </div>
        <h1 class="text-xl font-bold">Phân tích khoảng cách kỹ năng</h1>
        <p class="text-sm text-base-content/50 mt-0.5">Khoảng cách so với benchmark cấp độ tiếp theo. Sắp xếp theo tổng gap giảm dần.</p>
    </div>
    <div class="flex gap-2">
        <a href="{{ route('report.competency.export') }}" class="btn btn-outline btn-sm">Xuất Excel</a>
        <a href="{{ route('report.competency.index') }}"  class="btn btn-ghost btn-sm">← Tổng quan</a>
    </div>
</div>

@php
$maturityVi = [
    'DIGITAL_BEGINNER'     => 'Khởi đầu',
    'DIGITAL_AWARE'        => 'Nhận thức',
    'DIGITAL_PRACTITIONER' => 'Thực hành',
    'DIGITAL_PROFESSIONAL' => 'Chuyên nghiệp',
    'DIGITAL_LEADER'       => 'Dẫn dắt',
];
@endphp

<div x-data="{ dept: '' }">

    {{-- Filter --}}
    <div class="flex items-center gap-3 mb-4">
        <label class="text-sm text-base-content/60">Lọc phòng ban:</label>
        <select x-model="dept" class="select select-bordered select-sm w-56">
            <option value="">Tất cả</option>
            @foreach($departments as $d)
            <option value="{{ $d }}">{{ $d }}</option>
            @endforeach
        </select>
        <span class="text-xs text-base-content/40">{{ $gaps->count() }} nhân sự</span>
    </div>

    @if($gaps->isEmpty())
    <div class="card bg-base-100 border border-base-200 shadow-sm">
        <div class="card-body items-center text-center py-12">
            <p class="text-base-content/40 text-sm">Chưa có dữ liệu khoảng cách kỹ năng.</p>
        </div>
    </div>
    @else
    <div class="card bg-base-100 border border-base-200 shadow-sm overflow-hidden">
        <div class="overflow-x-auto">
            <table class="table table-sm w-full">
                <thead>
                    <tr class="bg-base-200/50 text-xs text-base-content/60">
                        <th>Họ tên</th>
                        <th>Phòng ban</th>
                        <th class="text-center">Hiện tại</th>
                        <th class="text-center">Mục tiêu</th>
                        @foreach(['D1','D2','D3','D4','D5','D6'] as $d)
                        <th class="text-center">Gap {{ $d }}</th>
                        @endforeach
                        <th class="text-center">Tổng Gap</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($gaps as $item)
                    @php
                        $deptName = $item['profile']->employee?->department?->name ?? '—';
                    @endphp
                    <tr class="hover:bg-base-200/30 transition-colors border-b border-base-200"
                        x-show="dept === '' || dept === '{{ $deptName }}'">
                        <td class="font-medium text-sm">{{ $item['profile']->employee?->full_name ?? '—' }}</td>
                        <td class="text-sm text-base-content/60">{{ $deptName }}</td>
                        <td class="text-center">
                            <span class="badge badge-ghost badge-xs">{{ $maturityVi[$item['profile']->tdwcf_maturity_level] ?? '—' }}</span>
                        </td>
                        <td class="text-center">
                            <span class="badge badge-outline badge-xs">{{ $maturityVi[$item['nextLevel']] ?? '—' }}</span>
                        </td>
                        @foreach(['D1','D2','D3','D4','D5','D6'] as $d)
                        @php
                            $g = round($item['gaps'][$d], 1);
                            $bc = $g > 20 ? 'badge-error' : ($g > 10 ? 'badge-warning' : 'badge-success');
                        @endphp
                        <td class="text-center">
                            @if($g > 0)
                            <span class="badge {{ $bc }} badge-xs">{{ $g }}</span>
                            @else
                            <span class="text-success text-xs">✓</span>
                            @endif
                        </td>
                        @endforeach
                        @php
                            $total = round($item['totalGap'], 1);
                            $tbc = $total > 60 ? 'badge-error' : ($total > 30 ? 'badge-warning' : 'badge-success');
                        @endphp
                        <td class="text-center">
                            <span class="badge {{ $tbc }} badge-sm font-semibold">{{ $total }}</span>
                        </td>
                    </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    </div>
    @endif

</div>

@endsection
