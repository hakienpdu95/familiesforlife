@extends('layouts.backend')
@section('title', 'Phản ánh mã QR')

@section('content')
@if(session('success'))
<div class="alert alert-success mb-4 text-sm"><span>{{ session('success') }}</span></div>
@endif

<div class="flex flex-wrap items-center justify-between gap-3 mb-5">
    <div>
        <h1 class="text-2xl font-bold text-base-content">Phản ánh mã QR</h1>
        <p class="text-sm text-base-content/50 mt-0.5">Phản ánh do người quét gửi từ trang truy xuất /p/{mã}</p>
    </div>
</div>

<div role="tablist" class="tabs tabs-boxed inline-flex mb-4">
    <a role="tab" href="{{ route('backend.qr-reports.index') }}" class="tab gap-1.5 {{ $status === 'new' ? 'tab-active' : '' }}">
        Chưa xử lý <span class="badge badge-sm {{ ($counts['new'] ?? 0) > 0 ? 'badge-error' : 'badge-ghost' }}">{{ $counts['new'] ?? 0 }}</span>
    </a>
    <a role="tab" href="{{ route('backend.qr-reports.index', ['status' => 'resolved']) }}" class="tab gap-1.5 {{ $status === 'resolved' ? 'tab-active' : '' }}">
        Đã xử lý <span class="badge badge-sm badge-ghost">{{ $counts['resolved'] ?? 0 }}</span>
    </a>
</div>

<div class="card bg-base-100 shadow-sm border border-base-200">
    <div class="overflow-x-auto">
        <table class="table table-sm">
            <thead>
                <tr>
                    <th>Thời gian</th>
                    <th>Mã QR / Đối tượng</th>
                    <th>Lý do</th>
                    <th class="min-w-[280px]">Nội dung</th>
                    <th>Liên hệ</th>
                    <th class="text-right">{{ $status === 'new' ? 'Thao tác' : 'Người xử lý' }}</th>
                </tr>
            </thead>
            <tbody>
                @forelse($reports as $report)
                    @php($target = $report->identity?->target)
                    <tr class="align-top">
                        <td class="text-xs whitespace-nowrap">{{ $report->created_at->format('d/m/Y H:i') }}</td>
                        <td class="text-xs">
                            @if($report->identity)
                                <a href="{{ $report->identity->publicUrl() }}" target="_blank" rel="noopener" class="font-mono font-semibold link link-primary">{{ $report->identity->qr_code }}</a>
                            @endif
                            <p class="text-base-content/60">{{ class_basename((string) \Illuminate\Database\Eloquent\Relations\Relation::getMorphedModel($report->identity?->target_type) ?: $report->identity?->target_type) }}</p>
                            <p class="font-medium">{{ $target?->name ?? $target?->mainTranslation()?->title ?? '(đã xoá)' }}</p>
                        </td>
                        <td><span class="badge badge-sm {{ $report->reason === 'suspected_fake' ? 'badge-error' : 'badge-warning' }} whitespace-nowrap">{{ $report->reasonLabel() }}</span></td>
                        <td class="text-sm whitespace-pre-line">{{ $report->content }}</td>
                        <td class="text-xs">{{ $report->contact ?: '—' }}</td>
                        <td class="text-right">
                            @if($report->status === 'new')
                                <form method="POST" action="{{ route('backend.qr-reports.resolve', $report) }}">
                                    @csrf
                                    <button class="btn btn-success btn-xs">Đã xử lý</button>
                                </form>
                            @else
                                <span class="text-xs text-base-content/60">{{ $report->resolvedBy?->name ?? '—' }}<br>{{ $report->resolved_at?->format('d/m/Y H:i') }}</span>
                            @endif
                        </td>
                    </tr>
                @empty
                    <tr><td colspan="6" class="text-center text-sm text-base-content/40 py-10">Không có phản ánh nào.</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>

<div class="mt-4">{{ $reports->links() }}</div>
@endsection
