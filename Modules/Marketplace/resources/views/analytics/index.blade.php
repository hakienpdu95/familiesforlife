@extends('layouts.backend')

@section('title', 'Marketplace Analytics')


@section('content')
<div class="px-6 py-4 max-w-6xl mx-auto space-y-6">

    <div class="flex items-center justify-between">
        <h1 class="text-xl font-bold">Marketplace Analytics</h1>
        @if(($stats->out_of_sync_count ?? 0) > 0)
        <div class="alert alert-warning py-2 px-4 text-sm flex items-center gap-2">
            <svg class="w-4 h-4 shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01M10.29 3.86L1.82 18a2 2 0 001.71 3h16.94a2 2 0 001.71-3L13.71 3.86a2 2 0 00-3.42 0z"/></svg>
            <span>{{ $stats->out_of_sync_count }} tin lỗi thời so với Job Posting — cần re-sync</span>
        </div>
        @endif
    </div>

    {{-- ── Summary stats ─────────────────────────────────────────────── --}}
    <div class="stats stats-horizontal shadow w-full">
        <div class="stat place-items-center">
            <div class="stat-title">Tổng tin</div>
            <div class="stat-value text-2xl">{{ number_format($stats->total_listings ?? 0) }}</div>
            <div class="stat-desc">{{ $stats->active_listings ?? 0 }} đang active</div>
        </div>
        <div class="stat place-items-center">
            <div class="stat-title">Lượt xem</div>
            <div class="stat-value text-2xl text-info">{{ number_format($stats->total_views ?? 0) }}</div>
        </div>
        <div class="stat place-items-center">
            <div class="stat-title">Ứng viên</div>
            <div class="stat-value text-2xl text-primary">{{ number_format($stats->total_applications ?? 0) }}</div>
            <div class="stat-desc">{{ $pending }} chờ xử lý</div>
        </div>
        <div class="stat place-items-center">
            <div class="stat-title">Đã tuyển</div>
            <div class="stat-value text-2xl text-success">{{ number_format($hired) }}</div>
        </div>
        <div class="stat place-items-center">
            <div class="stat-title">Bookmark</div>
            <div class="stat-value text-2xl">{{ number_format($stats->total_bookmarks ?? 0) }}</div>
        </div>
        @if(($stats->out_of_sync_count ?? 0) > 0)
        <div class="stat place-items-center">
            <div class="stat-title">Lỗi thời JP</div>
            <div class="stat-value text-2xl text-warning">{{ $stats->out_of_sync_count }}</div>
            <div class="stat-desc">cần re-sync</div>
        </div>
        @endif
    </div>

    {{-- ── Per-listing breakdown ──────────────────────────────────────── --}}
    <div class="card bg-base-100 shadow-sm">
        <div class="card-body p-0">
            <table class="table table-sm">
                <thead>
                    <tr class="text-xs opacity-60">
                        <th>Tin đăng</th>
                        <th class="text-right">Lượt xem</th>
                        <th class="text-right">Ứng viên</th>
                        <th class="text-right">Conversion</th>
                        <th class="text-right">Bookmark</th>
                        <th>Trạng thái</th>
                        <th></th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($listings as $item)
                    @php $l = $item['model'] @endphp
                    <tr class="hover">
                        <td>
                            <div class="font-medium text-sm">{{ Str::limit($l->title, 55) }}</div>
                            <div class="text-xs opacity-50">{{ $l->listing_type?->label() }} · {{ $l->created_at?->format('d/m/Y') }}</div>
                            @if($l->jp_sync_status?->value === 'out_of_sync')
                            <span class="badge badge-warning badge-xs mt-0.5">Lỗi thời JP</span>
                            @endif
                        </td>
                        <td class="text-right text-sm">{{ number_format($l->view_count) }}</td>
                        <td class="text-right text-sm font-semibold text-primary">{{ number_format($l->application_count) }}</td>
                        <td class="text-right text-sm">
                            @if($item['conversion_rate'] > 0)
                            <span class="{{ $item['conversion_rate'] >= 5 ? 'text-success' : 'text-base-content/60' }}">
                                {{ $item['conversion_rate'] }}%
                            </span>
                            @else
                            <span class="opacity-30">—</span>
                            @endif
                        </td>
                        <td class="text-right text-sm">{{ number_format($l->bookmark_count) }}</td>
                        <td>
                            <span class="badge {{ $l->status?->badgeClass() }} badge-sm">{{ $l->status?->label() }}</span>
                        </td>
                        <td>
                            <a href="{{ route('backend.marketplace.listings.show', $l) }}"
                               class="btn btn-ghost btn-xs">Xem</a>
                        </td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="7" class="text-center py-8 opacity-40 text-sm">Chưa có tin đăng nào.</td>
                    </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>

</div>
@endsection
