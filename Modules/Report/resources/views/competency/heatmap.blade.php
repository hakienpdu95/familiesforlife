@extends('layouts.backend')
@section('title', 'Heatmap Năng lực — Phòng ban')

@section('content')

<div class="flex flex-wrap items-center justify-between gap-3 mb-5">
    <div>
        <div class="text-xs breadcrumbs text-base-content/40 mb-1">
            <ul><li><a href="{{ route('report.competency.index') }}">Năng lực số</a></li><li>Heatmap</li></ul>
        </div>
        <h1 class="text-xl font-bold">Heatmap năng lực theo phòng ban</h1>
        <p class="text-sm text-base-content/50 mt-0.5">Màu ô: <span class="text-error">< 40</span> · <span class="text-warning">40–59</span> · <span class="text-info">60–79</span> · <span class="text-success">≥ 80</span></p>
    </div>
    <div class="flex gap-2">
        <a href="{{ route('report.competency.export') }}" class="btn btn-outline btn-sm">Xuất Excel</a>
        <a href="{{ route('report.competency.index') }}"  class="btn btn-ghost btn-sm">← Tổng quan</a>
    </div>
</div>

@if($heatmap->isEmpty())
<div class="card bg-base-100 border border-base-200 shadow-sm">
    <div class="card-body items-center text-center py-12">
        <p class="text-base-content/40 text-sm">Chưa có dữ liệu hồ sơ năng lực.</p>
    </div>
</div>
@else

<div class="card bg-base-100 border border-base-200 shadow-sm" x-data="heatmapTable()">
    <div class="overflow-x-auto">
        <table class="table table-sm w-full">
            <thead>
                <tr class="bg-base-200/50 text-xs text-base-content/60">
                    <th class="w-48 cursor-pointer select-none" @click="sort('dept')">
                        Phòng ban <span x-text="sortKey==='dept' ? (sortAsc?'↑':'↓') : ''"></span>
                    </th>
                    <th class="text-center cursor-pointer" @click="sort('count')">Nhân sự <span x-text="sortKey==='count' ? (sortAsc?'↑':'↓') : ''"></span></th>
                    @foreach(['D1','D2','D3','D4','D5','D6'] as $d)
                    <th class="text-center cursor-pointer" @click="sort('{{ $d }}')">
                        {{ $d }} <span x-text="sortKey==='{{ $d }}' ? (sortAsc?'↑':'↓') : ''"></span>
                    </th>
                    @endforeach
                    <th class="text-center cursor-pointer" @click="sort('trust')">Trust <span x-text="sortKey==='trust' ? (sortAsc?'↑':'↓') : ''"></span></th>
                </tr>
            </thead>
            <tbody>
                @php
                function heatCell(float $val): string {
                    if ($val >= 80) return 'bg-success/15 text-success font-semibold';
                    if ($val >= 60) return 'bg-info/15 text-info';
                    if ($val >= 40) return 'bg-warning/15 text-warning';
                    return 'bg-error/15 text-error';
                }
                @endphp
                @foreach($heatmap as $dept => $row)
                <tr class="hover:bg-base-200/40 transition-colors border-b border-base-200">
                    <td class="font-medium text-sm">{{ $dept }}</td>
                    <td class="text-center text-sm">{{ $row['count'] }}</td>
                    @foreach(['D1','D2','D3','D4','D5','D6'] as $d)
                    <td class="text-center text-sm {{ heatCell($row[$d]) }} rounded">{{ $row[$d] }}</td>
                    @endforeach
                    <td class="text-center text-sm {{ heatCell($row['trust']) }} rounded">{{ $row['trust'] }}</td>
                </tr>
                @endforeach
            </tbody>
            <tfoot>
                <tr class="bg-base-200/60 font-semibold text-sm border-t-2 border-base-300">
                    <td>Toàn tổ chức</td>
                    <td class="text-center">{{ $overall['count'] }}</td>
                    @foreach(['D1','D2','D3','D4','D5','D6'] as $d)
                    <td class="text-center {{ heatCell($overall[$d]) }} rounded">{{ $overall[$d] }}</td>
                    @endforeach
                    <td class="text-center {{ heatCell($overall['trust']) }} rounded">{{ $overall['trust'] }}</td>
                </tr>
            </tfoot>
        </table>
    </div>
</div>

@push('scripts')
<script>
function heatmapTable() {
    return {
        sortKey: 'dept',
        sortAsc: true,
        sort(key) {
            if (this.sortKey === key) this.sortAsc = !this.sortAsc;
            else { this.sortKey = key; this.sortAsc = true; }
        }
    }
}
</script>
@endpush

@endif

@endsection
