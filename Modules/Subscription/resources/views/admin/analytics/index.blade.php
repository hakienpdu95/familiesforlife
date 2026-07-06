@extends('layouts.backend')

@section('title', 'Subscription Analytics')

@section('content')
<div class="p-6 max-w-5xl mx-auto space-y-6">

    <div class="flex flex-wrap items-center justify-between gap-4">
        <h1 class="text-xl font-bold">Subscription Analytics</h1>

        <form method="GET" class="flex gap-2 items-center">
            <select name="year" class="select select-sm select-bordered">
                @foreach(range(now()->year, now()->year - 2) as $y)
                    <option value="{{ $y }}" {{ $y == $year ? 'selected' : '' }}>{{ $y }}</option>
                @endforeach
            </select>
            <select name="month" class="select select-sm select-bordered">
                @foreach(range(1, 12) as $m)
                    <option value="{{ $m }}" {{ $m == $month ? 'selected' : '' }}>
                        {{ \Carbon\Carbon::create(null, $m)->translatedFormat('F') }}
                    </option>
                @endforeach
            </select>
            <button class="btn btn-sm btn-primary">Xem</button>
        </form>
    </div>

    {{-- KPI Cards --}}
    <div class="grid grid-cols-2 md:grid-cols-4 gap-4">
        <div class="stat bg-base-100 shadow rounded-xl">
            <div class="stat-title">MRR</div>
            <div class="stat-value text-success text-2xl">{{ number_format($mrr, 0, ',', '.') }}</div>
            <div class="stat-desc">VND / {{ sprintf('%02d-%04d', $month, $year) }}</div>
        </div>
        <div class="stat bg-base-100 shadow rounded-xl">
            <div class="stat-title">Orgs hoạt động</div>
            <div class="stat-value text-2xl">{{ number_format($active_org_count) }}</div>
            <div class="stat-desc">Tổng</div>
        </div>
        <div class="stat bg-base-100 shadow rounded-xl">
            <div class="stat-title">Đăng ký mới</div>
            <div class="stat-value text-info text-2xl">{{ $new_count }}</div>
            <div class="stat-desc">Tháng này</div>
        </div>
        <div class="stat bg-base-100 shadow rounded-xl">
            <div class="stat-title">Churn</div>
            <div class="stat-value text-error text-2xl">{{ $churn_count }}</div>
            <div class="stat-desc">Tháng này</div>
        </div>
    </div>

    {{-- Plan distribution --}}
    <div class="card bg-base-100 shadow">
        <div class="card-body">
            <h2 class="card-title text-base">Phân bổ theo Plan</h2>
            <div class="overflow-x-auto">
                <table class="table table-sm">
                    <thead>
                        <tr>
                            <th>Plan</th>
                            <th>Slug</th>
                            <th class="text-right">Giá / tháng</th>
                            <th class="text-right">Số orgs</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($plan_distribution as $plan)
                        <tr>
                            <td class="font-medium">{{ $plan->name }}</td>
                            <td><span class="badge badge-ghost badge-sm">{{ $plan->slug }}</span></td>
                            <td class="text-right">{{ number_format($plan->price, 0, ',', '.') }}</td>
                            <td class="text-right font-bold">{{ $plan->active_count }}</td>
                        </tr>
                        @empty
                        <tr><td colspan="4" class="text-center text-base-content/40">Chưa có dữ liệu</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>

</div>
@endsection
