@extends('layouts.backend')
@section('title', 'Kết quả theo đơn vị — ' . $campaign->title)

@section('content')

<div class="flex items-center gap-2 text-sm text-base-content/50 mb-4">
    <a href="{{ route('campaigns.admin.index') }}" class="hover:text-primary">Campaigns</a>
    <svg class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/></svg>
    <a href="{{ route('campaigns.admin.show', $campaign->uuid) }}" class="hover:text-primary">{{ $campaign->title }}</a>
    <svg class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/></svg>
    <a href="{{ route('campaigns.admin.results', $campaign->uuid) }}" class="hover:text-primary">Kết quả</a>
    <svg class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/></svg>
    <span>Theo đơn vị</span>
</div>

<div class="flex items-center justify-between gap-3 mb-5 flex-wrap">
    <div>
        <h1 class="text-xl font-bold">Kết quả theo đơn vị</h1>
        <p class="text-sm text-base-content/50 mt-0.5">{{ $campaign->title }} · Sắp xếp theo TDWCF TB giảm dần</p>
    </div>
    <div class="flex gap-2">
        <a href="{{ route('campaigns.admin.export', $campaign->uuid) }}" class="btn btn-outline btn-sm">Xuất Excel</a>
        <a href="{{ route('campaigns.admin.results', $campaign->uuid) }}" class="btn btn-ghost btn-sm">← Ranking</a>
    </div>
</div>

@if($unitStats->isEmpty())
<div class="card bg-base-100 border border-base-200 shadow-sm">
    <div class="card-body items-center text-center py-16">
        <p class="text-base-content/40 text-sm">Chưa có ứng viên hoàn thành campaign này.</p>
    </div>
</div>
@else

<div class="card bg-base-100 border border-base-200 shadow-sm">
    <div class="overflow-x-auto">
        <table class="table table-sm w-full">
            <thead>
                <tr class="bg-base-200/50 text-xs text-base-content/60">
                    <th>Đơn vị / Phòng ban</th>
                    <th class="text-center">Tham gia</th>
                    <th class="text-center">Đã mời</th>
                    <th class="text-center">Tỉ lệ mời</th>
                    <th class="text-center">TDWCF TB</th>
                    <th class="text-center">Sandbox TB</th>
                </tr>
            </thead>
            <tbody>
                @foreach($unitStats as $dept => $stat)
                @php
                    $invitePct = $stat['count'] ? round($stat['invited'] / $stat['count'] * 100) : 0;
                    $scoreColor = $stat['avg_tdwcf'] >= 60 ? 'text-success' : ($stat['avg_tdwcf'] >= 40 ? 'text-warning' : 'text-error');
                @endphp
                <tr class="border-b border-base-200 hover:bg-base-200/30 transition-colors">
                    <td class="font-medium text-sm">{{ $dept }}</td>
                    <td class="text-center text-sm">{{ $stat['count'] }}</td>
                    <td class="text-center text-sm">{{ $stat['invited'] }}</td>
                    <td class="text-center">
                        <div class="flex items-center justify-center gap-2">
                            <div class="w-16 bg-base-200 rounded-full h-1.5">
                                <div class="bg-primary h-1.5 rounded-full" style="width: {{ $invitePct }}%"></div>
                            </div>
                            <span class="text-xs text-base-content/50">{{ $invitePct }}%</span>
                        </div>
                    </td>
                    <td class="text-center">
                        <span class="font-semibold {{ $scoreColor }}">{{ $stat['avg_tdwcf'] }}</span>
                    </td>
                    <td class="text-center text-sm text-base-content/60">
                        {{ $stat['avg_sb'] ?: '—' }}
                    </td>
                </tr>
                @endforeach
            </tbody>
            <tfoot>
                @php
                    $totalCount  = $unitStats->sum('count');
                    $totalInvite = $unitStats->sum('invited');
                    $allAvgTdwcf = $totalCount ? round($unitStats->sum(fn($s) => $s['avg_tdwcf'] * $s['count']) / $totalCount, 1) : 0;
                @endphp
                <tr class="bg-base-200/60 font-semibold text-sm border-t-2 border-base-300">
                    <td>Tổng cộng</td>
                    <td class="text-center">{{ $totalCount }}</td>
                    <td class="text-center">{{ $totalInvite }}</td>
                    <td class="text-center text-xs text-base-content/50">
                        {{ $totalCount ? round($totalInvite / $totalCount * 100) : 0 }}%
                    </td>
                    <td class="text-center {{ $allAvgTdwcf >= 60 ? 'text-success' : ($allAvgTdwcf >= 40 ? 'text-warning' : 'text-error') }}">
                        {{ $allAvgTdwcf }}
                    </td>
                    <td></td>
                </tr>
            </tfoot>
        </table>
    </div>
</div>

@endif

@endsection
