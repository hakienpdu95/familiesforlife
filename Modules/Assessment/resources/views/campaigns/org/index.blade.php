@extends('layouts.backend')
@section('title', 'Quản lý Campaigns')

@section('content')

<div class="flex flex-wrap items-center justify-between gap-3 mb-6">
    <div>
        <h1 class="text-2xl font-bold">Open Assessment Campaigns</h1>
        <p class="text-sm text-base-content/50 mt-0.5">Tạo chiến dịch đánh giá ứng viên mở, xem kết quả ẩn danh</p>
    </div>
    <a href="{{ route('campaigns.admin.create') }}" class="btn btn-primary gap-1.5">
        <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/></svg>
        Tạo campaign
    </a>
</div>

@if(session('success'))
<div class="alert alert-success mb-4"><span>{{ session('success') }}</span></div>
@endif

@if($campaigns->isEmpty())
<div class="card bg-base-100 border border-base-200 shadow-sm">
    <div class="card-body items-center text-center py-16">
        <div class="w-16 h-16 rounded-full bg-base-200 flex items-center justify-center mb-4">
            <svg class="w-8 h-8 text-base-content/30" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2m-3 7h3m-3 4h3m-6-4h.01M9 16h.01"/></svg>
        </div>
        <h2 class="text-lg font-bold">Chưa có campaign nào</h2>
        <p class="text-base-content/50 text-sm mb-4">Tạo chiến dịch đánh giá để tìm kiếm ứng viên tiềm năng</p>
        <a href="{{ route('campaigns.admin.create') }}" class="btn btn-primary btn-sm">Tạo campaign đầu tiên</a>
    </div>
</div>
@else
<div class="card bg-base-100 border border-base-200 shadow-sm">
    <div class="overflow-x-auto">
        <table class="table">
            <thead>
                <tr>
                    <th>Campaign</th>
                    <th>Trạng thái</th>
                    <th>Trust LV</th>
                    <th>Tham gia</th>
                    <th>Hoàn thành</th>
                    <th>Hạn nộp</th>
                    <th></th>
                </tr>
            </thead>
            <tbody>
                @foreach($campaigns as $campaign)
                <tr class="hover">
                    <td>
                        <p class="font-semibold">{{ $campaign->title }}</p>
                        @if($campaign->target_job_title_label)
                        <p class="text-xs text-base-content/50">{{ $campaign->target_job_title_label }}</p>
                        @endif
                    </td>
                    <td>
                        <span class="badge {{ $campaign->status->badgeClass() }} badge-sm">{{ $campaign->status->label() }}</span>
                    </td>
                    <td class="text-sm">Lv{{ $campaign->min_trust_level }}+</td>
                    <td class="font-semibold">{{ $campaign->participations_count }}</td>
                    <td class="font-semibold text-success">{{ $campaign->completed_count }}</td>
                    <td class="text-sm text-base-content/60">
                        {{ $campaign->open_until?->format('d/m/Y') ?? '—' }}
                    </td>
                    <td>
                        <div class="flex gap-1.5">
                            <a href="{{ route('campaigns.admin.results', $campaign->uuid) }}"
                               class="btn btn-ghost btn-xs">Kết quả</a>
                            <a href="{{ route('campaigns.admin.show', $campaign->uuid) }}"
                               class="btn btn-ghost btn-xs">Chi tiết</a>
                        </div>
                    </td>
                </tr>
                @endforeach
            </tbody>
        </table>
    </div>
</div>
<div class="mt-4">{{ $campaigns->links() }}</div>
@endif

@endsection
