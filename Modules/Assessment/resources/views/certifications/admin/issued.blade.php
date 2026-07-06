@extends('layouts.backend')
@section('title', 'Danh sách chứng nhận đã cấp')

@section('content')

@if(session('success'))
<div class="alert alert-success mb-4 py-2 px-4 text-sm">{{ session('success') }}</div>
@endif
@if(session('error'))
<div class="alert alert-error mb-4 py-2 px-4 text-sm">{{ session('error') }}</div>
@endif

<div class="flex flex-wrap items-center justify-between gap-3 mb-6">
    <div>
        <div class="flex items-center gap-2">
            <a href="{{ route('backend.certs-admin.index') }}" class="btn btn-ghost btn-sm px-2">
                <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18"/></svg>
            </a>
            <h1 class="text-xl font-bold">Danh sách chứng nhận đã cấp</h1>
        </div>
        <p class="text-sm text-base-content/50 mt-0.5 ml-10">{{ $certs->total() }} chứng nhận</p>
    </div>
    <a href="{{ route('backend.certs-admin.issue-form') }}" class="btn btn-primary btn-sm gap-1.5">
        <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/></svg>
        Cấp thủ công
    </a>
</div>

@php
$statusBadge = [
    'active'  => ['Hiệu lực', 'badge-success'],
    'expired' => ['Hết hạn', 'badge-warning'],
    'revoked' => ['Đã thu hồi', 'badge-error'],
];
@endphp

<div class="overflow-x-auto">
    <table class="table table-sm bg-base-100 rounded-xl border border-base-200">
        <thead>
            <tr class="text-xs text-base-content/40 uppercase tracking-wide border-b border-base-200">
                <th>Nhân viên</th>
                <th>Chứng nhận</th>
                <th>Cấp độ</th>
                <th>Ngày cấp</th>
                <th>Hết hạn</th>
                <th>Trạng thái</th>
                <th>Điểm khi cấp</th>
                <th>QR</th>
                <th></th>
            </tr>
        </thead>
        <tbody>
            @forelse($certs as $cert)
            @php
                [$statusLabel, $statusCls] = $statusBadge[$cert->status] ?? [$cert->status, 'badge-ghost'];
                $employee = $cert->profile?->employee;
            @endphp
            <tr class="hover:bg-base-50 border-b border-base-100">
                <td>
                    <div class="font-medium text-sm">{{ $employee?->full_name ?? '—' }}</div>
                    <div class="text-xs text-base-content/40">{{ $employee?->employee_code }}</div>
                </td>
                <td>
                    <div class="text-sm font-medium">{{ $cert->definition?->name ?? '—' }}</div>
                    <div class="text-xs font-mono text-base-content/40">{{ $cert->definition?->cert_code }}</div>
                </td>
                <td>
                    <span class="badge badge-xs badge-outline">{{ $cert->definition?->level_code ?? '—' }}</span>
                </td>
                <td class="text-sm">{{ $cert->issued_at?->format('d/m/Y') }}</td>
                <td class="text-sm">
                    @if($cert->expires_at)
                        <span class="{{ $cert->expires_at->isPast() ? 'text-error' : ($cert->expires_at->diffInDays() < 30 ? 'text-warning' : '') }}">
                            {{ $cert->expires_at->format('d/m/Y') }}
                        </span>
                    @else
                        —
                    @endif
                </td>
                <td><span class="badge badge-xs {{ $statusCls }}">{{ $statusLabel }}</span></td>
                <td class="text-sm">
                    @if($cert->composite_score_at_issue !== null)
                        <span class="font-mono">{{ round($cert->composite_score_at_issue, 1) }}</span>
                    @else
                        —
                    @endif
                </td>
                <td>
                    @if($cert->qr_code_url)
                    <a href="{{ route('assessment.cert.verify', $cert->certificate_number) }}"
                       target="_blank"
                       class="btn btn-ghost btn-xs gap-1"
                       title="Xem trang xác minh">
                        <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v1m6 11h2m-6 0h-2v4m0-11v3m0 0h.01M12 12h4.24M16.24 12l1.76-1.76M3 3h2.01M3 3v2.01M3 21h2.01M3 21v-2.01M21 3h-2.01M21 3v2.01M21 21h-2.01M21 21v-2.01M7 7h.01M7 17h.01M17 7h.01"/></svg>
                        QR
                    </a>
                    @else
                    <span class="text-xs text-base-content/30">—</span>
                    @endif
                </td>
                <td>
                    @if($cert->status === 'active')
                    <button class="btn btn-ghost btn-xs text-error"
                            onclick="document.getElementById('revoke-{{ $cert->id }}').showModal()">
                        Thu hồi
                    </button>
                    @endif
                </td>
            </tr>

            {{-- Revoke modal --}}
            @if($cert->status === 'active')
            <dialog id="revoke-{{ $cert->id }}" class="modal">
                <div class="modal-box max-w-sm">
                    <h3 class="font-bold text-base mb-2">Thu hồi chứng nhận</h3>
                    <p class="text-sm text-base-content/60 mb-4">
                        Thu hồi <strong>{{ $cert->definition?->cert_code }}</strong> của
                        <strong>{{ $employee?->full_name }}</strong>. Không thể hoàn tác.
                    </p>
                    <form method="POST" action="{{ route('backend.certs-admin.revoke', $cert) }}">
                        @csrf @method('PATCH')
                        <div class="form-control mb-4">
                            <label class="label py-1"><span class="label-text text-xs font-medium">Lý do thu hồi <span class="text-error">*</span></span></label>
                            <textarea name="revoked_reason" rows="2" class="textarea textarea-bordered text-sm" required
                                      placeholder="Lý do thu hồi..."></textarea>
                        </div>
                        <div class="modal-action mt-0">
                            <form method="dialog"><button class="btn btn-ghost btn-sm">Huỷ</button></form>
                            <button type="submit" class="btn btn-error btn-sm">Thu hồi</button>
                        </div>
                    </form>
                </div>
                <form method="dialog" class="modal-backdrop"><button>close</button></form>
            </dialog>
            @endif
            @empty
            <tr><td colspan="8" class="text-center py-10 text-base-content/30 text-sm">Chưa có chứng nhận nào được cấp.</td></tr>
            @endforelse
        </tbody>
    </table>
</div>

@if($certs->hasPages())
<div class="mt-4">{{ $certs->links() }}</div>
@endif

@endsection
