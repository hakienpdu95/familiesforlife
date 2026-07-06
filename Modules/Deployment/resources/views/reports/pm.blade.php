@extends('layouts.backend')
@section('title', 'Báo cáo PM — ' . $vertical->label())

@section('content')
<div class="space-y-5">

    <div class="flex flex-wrap items-center justify-between gap-3">
        <div>
            <h1 class="text-xl font-bold">Báo cáo Quản lý Dự án</h1>
            <p class="text-sm text-base-content/50">{{ $vertical->label() }} · {{ now()->format('d/m/Y H:i') }}</p>
        </div>
        <div class="flex gap-2">
            <a href="{{ route('deployment.reports.pm', ['vertical' => $vertical->code(), 'format' => 'excel']) }}"
               class="btn btn-outline btn-sm">↓ Excel</a>
            <a href="{{ route('deployment.reports.pm', ['vertical' => $vertical->code(), 'format' => 'pdf']) }}"
               class="btn btn-outline btn-sm">↓ PDF</a>
        </div>
    </div>

    {{-- Summary row --}}
    <div class="grid grid-cols-2 lg:grid-cols-4 gap-4">
        <div class="stat bg-base-100 border border-base-200 rounded-box py-3">
            <div class="stat-title text-xs">Dự án</div>
            <div class="stat-value text-2xl">{{ $projects->count() }}</div>
        </div>
        <div class="stat bg-base-100 border border-base-200 rounded-box py-3">
            <div class="stat-title text-xs">{{ $vertical->targetLabel() }}</div>
            <div class="stat-value text-2xl">{{ $targets->count() }}</div>
        </div>
        <div class="stat bg-base-100 border border-base-200 rounded-box py-3">
            <div class="stat-title text-xs">Issues mở</div>
            <div class="stat-value text-2xl text-error">{{ $openIssues->count() }}</div>
        </div>
        <div class="stat bg-base-100 border border-base-200 rounded-box py-3">
            <div class="stat-title text-xs">Nhân sự</div>
            <div class="stat-value text-2xl">{{ $teamMembers->count() }}</div>
        </div>
    </div>

    {{-- HTX progress table --}}
    <div class="card bg-base-100 border border-base-200 shadow-sm overflow-hidden">
        <div class="px-4 py-3 border-b border-base-200 font-semibold text-sm">
            Tiến độ {{ $vertical->targetLabel() }}
        </div>
        <div class="overflow-x-auto">
            <table class="table table-sm">
                <thead class="bg-base-200 text-xs uppercase">
                    <tr>
                        <th>Tổ chức</th>
                        <th>Dự án</th>
                        <th>Phase</th>
                        <th>Tiến độ</th>
                        <th>Phụ trách</th>
                        <th>MST</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($targets as $t)
                    <tr class="hover">
                        <td class="font-medium">{{ $t->targetOrganization?->name ?? '—' }}</td>
                        <td class="text-sm">{{ $t->project?->name ?? '—' }}</td>
                        <td><span class="badge badge-outline badge-xs">{{ $t->current_phase }}</span></td>
                        <td>
                            <div class="flex items-center gap-2">
                                <div class="w-20 bg-base-200 rounded-full h-1.5">
                                    <div class="h-1.5 rounded-full bg-primary" style="width:{{ $t->overall_pct }}%"></div>
                                </div>
                                <span class="text-xs">{{ $t->overall_pct }}%</span>
                            </div>
                        </td>
                        <td class="text-sm">{{ $t->assignedEmployee?->full_name ?? '—' }}</td>
                        <td class="font-mono text-xs">{{ $t->targetOrganization?->tax_code ?? '—' }}</td>
                    </tr>
                    @empty
                    <tr><td colspan="6" class="text-center text-base-content/40 py-6">Chưa có dữ liệu.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>

    {{-- Open issues --}}
    @if($openIssues->isNotEmpty())
    <div class="card bg-base-100 border border-base-200 shadow-sm overflow-hidden">
        <div class="px-4 py-3 border-b border-base-200 font-semibold text-sm">Issues đang mở</div>
        <div class="overflow-x-auto">
            <table class="table table-sm">
                <thead class="bg-base-200 text-xs uppercase">
                    <tr>
                        <th>Tiêu đề</th>
                        <th>Severity</th>
                        <th>Tổ chức</th>
                        <th>Ngày tạo</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($openIssues as $issue)
                    <tr>
                        <td class="text-sm">{{ $issue->title }}</td>
                        <td><span class="badge badge-sm {{ $issue->severity?->badgeClass() ?? 'badge-ghost' }}">{{ $issue->severity?->label() }}</span></td>
                        <td class="text-sm">{{ $issue->target?->targetOrganization?->name ?? '—' }}</td>
                        <td class="text-xs text-base-content/50">{{ $issue->created_at?->format('d/m/Y') }}</td>
                    </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    </div>
    @endif

    {{-- Team --}}
    @if($teamMembers->isNotEmpty())
    <div class="card bg-base-100 border border-base-200 shadow-sm overflow-hidden">
        <div class="px-4 py-3 border-b border-base-200 font-semibold text-sm">Nhân sự</div>
        <div class="overflow-x-auto">
            <table class="table table-sm">
                <thead class="bg-base-200 text-xs uppercase">
                    <tr><th>Nhân viên</th><th>Dự án</th><th>Vai trò</th></tr>
                </thead>
                <tbody>
                    @foreach($teamMembers as $member)
                    <tr>
                        <td class="text-sm">{{ $member->employee?->full_name ?? '—' }}</td>
                        <td class="text-sm">{{ $member->project?->name ?? '—' }}</td>
                        <td><span class="badge badge-ghost badge-xs">{{ $member->role?->label() ?? $member->role }}</span></td>
                    </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    </div>
    @endif

</div>
@endsection
