@extends('layouts.backend')
@section('title', 'AICEM — Tổng quan')

@section('content')
<div>

    @foreach(['success', 'error'] as $type)
        @if(session($type))
        <div x-data="{ show: true }" x-show="show" x-init="setTimeout(() => show = false, 4000)"
             x-transition.opacity.duration.500ms
             class="alert alert-{{ $type }} mb-4 text-sm">
            <span>{{ session($type) }}</span>
            <button @click="show = false" class="btn btn-ghost btn-xs ml-auto">✕</button>
        </div>
        @endif
    @endforeach

    <div class="flex flex-wrap items-center justify-between gap-3 mb-5">
        <div>
            <h1 class="text-2xl font-bold text-base-content">AICEM — Tổng quan</h1>
            <p class="text-sm text-base-content/50 mt-0.5">Tình hình sử dụng trợ lý AI tháng {{ now()->format('m/Y') }}</p>
        </div>
        @can('aicem.config')
        <a href="{{ route('backend.aicem.settings') }}" class="btn btn-ghost btn-sm">Cấu hình AICEM</a>
        @endcan
    </div>

    <div class="grid grid-cols-2 lg:grid-cols-4 gap-4 mb-6">
        <div class="card bg-base-100 shadow-sm border border-base-200">
            <div class="card-body p-3">
                <p class="text-xs text-base-content/40 uppercase tracking-wide">Lượt chạy tháng này</p>
                <p class="text-2xl font-bold mt-1">{{ $stats['total_runs_this_month'] }}</p>
                <p class="text-xs text-base-content/50 mt-1">
                    {{ $stats['succeeded_runs_this_month'] }} thành công · {{ $stats['failed_runs_this_month'] }} thất bại
                </p>
            </div>
        </div>
        <div class="card bg-base-100 shadow-sm border border-base-200">
            <div class="card-body p-3">
                <p class="text-xs text-base-content/40 uppercase tracking-wide">Chi phí tháng này</p>
                <p class="text-2xl font-bold mt-1">${{ number_format($stats['cost_this_month'], 4) }}</p>
                @if($stats['budget_limit'] !== null)
                <p class="text-xs text-base-content/50 mt-1">
                    Hạn mức: ${{ number_format($stats['budget_limit'], 2) }} · đang giữ: ${{ number_format($stats['budget_reserved'], 4) }}
                </p>
                @else
                <p class="text-xs text-base-content/30 mt-1">Chưa đặt hạn mức (không giới hạn)</p>
                @endif
            </div>
        </div>
        <div class="card bg-base-100 shadow-sm border border-base-200">
            <div class="card-body p-3">
                <p class="text-xs text-base-content/40 uppercase tracking-wide">Prompt cache (Anthropic)</p>
                <p class="text-2xl font-bold mt-1">{{ number_format($stats['cache_read_tokens_this_month']) }}</p>
                <p class="text-xs text-base-content/50 mt-1">
                    token đọc từ cache · {{ number_format($stats['cache_creation_tokens_this_month']) }} token ghi mới vào cache
                </p>
            </div>
        </div>
        <div class="card bg-base-100 shadow-sm border border-base-200 col-span-2">
            <div class="card-body p-3">
                <p class="text-xs text-base-content/40 uppercase tracking-wide mb-2">Workflow dùng nhiều nhất (tháng này)</p>
                @forelse($stats['top_workflows'] as $wf)
                <div class="flex items-center justify-between text-sm py-0.5">
                    <span>{{ $wf->name }} <span class="text-base-content/30 font-mono text-xs">({{ $wf->subject_type }})</span></span>
                    <span class="badge badge-sm badge-ghost">{{ $wf->generation_runs_count }}</span>
                </div>
                @empty
                <p class="text-xs text-base-content/30">Chưa có dữ liệu.</p>
                @endforelse
            </div>
        </div>
    </div>

    {{-- Đối chiếu bài context-engineering (animalz.co) — "evaluation": tỷ lệ chấp nhận gợi ý là
         tín hiệu trực tiếp cho biết ngữ cảnh/knowledge base hiện tại có thực sự hữu ích không.
         Toàn thời gian (không giới hạn theo tháng) — mẫu cần đủ lớn mới có ý nghĩa thống kê. --}}
    <div class="card bg-base-100 shadow-sm border border-base-200 mb-6">
        <div class="card-body p-4">
            <p class="text-xs font-semibold text-base-content/40 uppercase tracking-wide mb-3">
                Tỷ lệ chấp nhận gợi ý (toàn thời gian)
            </p>
            @php($sa = $stats['suggestion_acceptance'])
            <div class="flex flex-wrap items-center gap-4 mb-3">
                <div>
                    <p class="text-2xl font-bold">
                        {{ $sa['acceptance_rate'] !== null ? number_format($sa['acceptance_rate'], 1) . '%' : '—' }}
                    </p>
                    <p class="text-xs text-base-content/40">
                        {{ $sa['accepted'] }} chấp nhận · {{ $sa['rejected'] }} từ chối
                        @if($sa['pending'] > 0) · {{ $sa['pending'] }} đang chờ @endif
                        @if($sa['stale'] > 0) · {{ $sa['stale'] }} đã cũ (stale) @endif
                    </p>
                </div>
            </div>
            @if(count($sa['by_field']))
            <div class="overflow-x-auto">
                <table class="table table-xs">
                    <thead class="text-xs uppercase tracking-wide text-base-content/40">
                        <tr>
                            <th>Trường</th>
                            <th class="text-right">Chấp nhận</th>
                            <th class="text-right">Từ chối</th>
                            <th class="text-right">Tỷ lệ</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($sa['by_field'] as $row)
                        <tr>
                            <td class="font-mono text-xs">{{ $row['field'] }}</td>
                            <td class="text-right">{{ $row['accepted'] }}</td>
                            <td class="text-right">{{ $row['rejected'] }}</td>
                            <td class="text-right">{{ $row['rate'] !== null ? number_format($row['rate'], 1) . '%' : '—' }}</td>
                        </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
            @else
            <p class="text-xs text-base-content/30">Chưa có gợi ý nào được quyết định.</p>
            @endif
        </div>
    </div>

    <div class="card bg-base-100 shadow-sm border border-base-200 overflow-hidden">
        <div class="card-body p-0">
            <p class="text-xs font-semibold text-base-content/40 uppercase tracking-wide p-4 pb-2">20 lượt chạy gần nhất</p>
            <div class="overflow-x-auto">
                <table class="table table-sm">
                    <thead class="bg-base-200/60 text-xs uppercase tracking-wide">
                        <tr>
                            <th>Workflow</th>
                            <th>Subject</th>
                            <th>Người chạy</th>
                            <th class="text-center">Trạng thái</th>
                            <th class="text-right">Chi phí</th>
                            <th>Lúc</th>
                        </tr>
                    </thead>
                    <tbody>
                    @forelse($stats['recent_runs'] as $run)
                    <tr class="hover">
                        <td class="text-sm">{{ $run->workflow?->name ?? '—' }}</td>
                        <td class="text-xs text-base-content/50">{{ $run->subject_type }}#{{ $run->subject_id }}</td>
                        <td class="text-sm">{{ $run->requestedBy?->name ?? '—' }}</td>
                        <td class="text-center">
                            <span class="badge badge-sm {{ match($run->status->value) {
                                'succeeded' => 'badge-success',
                                'failed' => 'badge-error',
                                'running' => 'badge-warning',
                                default => 'badge-ghost',
                            } }}">{{ $run->status->label() }}</span>
                        </td>
                        <td class="text-right text-xs font-mono">{{ $run->cost_usd !== null ? '$' . number_format($run->cost_usd, 4) : '—' }}</td>
                        <td class="text-xs text-base-content/40">{{ $run->created_at->format('d/m H:i') }}</td>
                    </tr>
                    @empty
                    <tr><td colspan="6" class="text-center py-8 text-base-content/40">Chưa có lượt chạy nào.</td></tr>
                    @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>
@endsection
