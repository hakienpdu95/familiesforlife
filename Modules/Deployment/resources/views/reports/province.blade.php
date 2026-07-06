@extends('layouts.backend')
@section('title', 'Báo cáo Tỉnh — ' . $vertical->label())

@section('content')
<div class="space-y-5">

    <div class="flex flex-wrap items-center justify-between gap-3">
        <div>
            <h1 class="text-xl font-bold">Báo cáo theo Tỉnh/Thành</h1>
            <p class="text-sm text-base-content/50">{{ $vertical->label() }} · {{ now()->format('d/m/Y H:i') }}</p>
        </div>
        <div class="flex gap-2">
            <a href="{{ route('deployment.reports.province', ['vertical' => $vertical->code(), 'format' => 'excel', 'province_code' => $provinceCode]) }}"
               class="btn btn-outline btn-sm">↓ Excel</a>
        </div>
    </div>

    {{-- Filter --}}
    <form method="GET" class="flex gap-3 items-end">
        <div class="form-control">
            <label class="label label-text text-xs">Lọc theo tỉnh</label>
            <select name="province_code" class="select select-bordered select-sm">
                <option value="">— Tất cả tỉnh —</option>
                @foreach($provinces as $code => $count)
                <option value="{{ $code }}" @selected($provinceCode === $code)>
                    {{ $code }} ({{ $count }})
                </option>
                @endforeach
            </select>
        </div>
        <button type="submit" class="btn btn-primary btn-sm">Lọc</button>
        @if($provinceCode)
        <a href="{{ route('deployment.reports.province', ['vertical' => $vertical->code()]) }}" class="btn btn-ghost btn-sm">Xóa lọc</a>
        @endif
    </form>

    {{-- Summary --}}
    <div class="grid grid-cols-2 lg:grid-cols-4 gap-4">
        <div class="stat bg-base-100 border border-base-200 rounded-box py-3">
            <div class="stat-title text-xs">Tổng</div>
            <div class="stat-value text-2xl">{{ $summary['total'] }}</div>
        </div>
        <div class="stat bg-base-100 border border-base-200 rounded-box py-3">
            <div class="stat-title text-xs">Đang triển khai</div>
            <div class="stat-value text-2xl text-info">{{ $summary['in_progress'] }}</div>
        </div>
        <div class="stat bg-base-100 border border-base-200 rounded-box py-3">
            <div class="stat-title text-xs">Hoàn thành</div>
            <div class="stat-value text-2xl text-success">{{ $summary['completed'] }}</div>
        </div>
        <div class="stat bg-base-100 border border-base-200 rounded-box py-3">
            <div class="stat-title text-xs">Draft</div>
            <div class="stat-value text-2xl text-base-content/50">{{ $summary['draft'] }}</div>
        </div>
    </div>

    {{-- Detail table --}}
    <div class="card bg-base-100 border border-base-200 shadow-sm overflow-hidden">
        <div class="overflow-x-auto">
            <table class="table table-sm">
                <thead class="bg-base-200 text-xs uppercase">
                    <tr>
                        <th>Tổ chức</th>
                        <th>Tỉnh/Thành</th>
                        <th>Phase</th>
                        <th>MST</th>
                        <th>Địa chỉ</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($targets as $t)
                    <tr class="hover">
                        <td class="font-medium text-sm">{{ $t->targetOrganization?->name ?? '—' }}</td>
                        <td class="text-sm">{{ $t->targetOrganization?->province_code ?? '—' }}</td>
                        <td><span class="badge badge-outline badge-xs">{{ $t->current_phase }}</span></td>
                        <td class="font-mono text-xs">{{ $t->targetOrganization?->tax_code ?? '—' }}</td>
                        <td class="text-xs text-base-content/60 max-w-xs truncate">{{ $t->targetOrganization?->full_address ?? '—' }}</td>
                    </tr>
                    @empty
                    <tr><td colspan="5" class="text-center text-base-content/40 py-8">Không có dữ liệu.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>

</div>
@endsection
