@extends('layouts.auth')
@section('title', 'Xác minh chứng nhận — ' . $cert->certificate_number)

@section('content')

@php
    $isValid   = $cert->status === 'active' && (! $cert->expires_at || $cert->expires_at->isFuture());
    $isExpired = $cert->status === 'expired' || ($cert->expires_at && $cert->expires_at->isPast());
    $isRevoked = $cert->status === 'revoked';

    $badgeClass = match(true) {
        $isValid   => 'badge-success',
        $isRevoked => 'badge-error',
        $isExpired => 'badge-warning',
        default    => 'badge-ghost',
    };
    $badgeLabel = match(true) {
        $isValid   => '✓ HỢP LỆ',
        $isRevoked => '✕ ĐÃ THU HỒI',
        $isExpired => '⚠ HẾT HẠN',
        default    => $cert->status,
    };
@endphp

<div class="w-full max-w-md mx-auto">
    <div class="card bg-base-100 shadow-lg border {{ $isValid ? 'border-success/30' : ($isRevoked ? 'border-error/30' : 'border-warning/30') }}">
        <div class="card-body p-6">

            {{-- Header --}}
            <div class="flex items-start justify-between gap-3 mb-4">
                <div>
                    <p class="text-xs text-base-content/40 uppercase tracking-wide mb-1">Xác minh chứng nhận số</p>
                    <p class="font-mono text-sm font-semibold text-base-content">{{ $cert->certificate_number }}</p>
                </div>
                <span class="badge {{ $badgeClass }} badge-lg font-bold shrink-0">{{ $badgeLabel }}</span>
            </div>

            <div class="divider my-2"></div>

            {{-- Cert info --}}
            <div class="space-y-3">
                <div class="flex justify-between items-start gap-2">
                    <span class="text-xs text-base-content/50 shrink-0">Người được cấp</span>
                    <span class="text-sm font-semibold text-right">
                        {{ $cert->profile?->employee?->full_name ?? '—' }}
                    </span>
                </div>
                <div class="flex justify-between items-start gap-2">
                    <span class="text-xs text-base-content/50 shrink-0">Chứng nhận</span>
                    <span class="text-sm font-medium text-right">{{ $cert->definition?->name ?? '—' }}</span>
                </div>
                <div class="flex justify-between items-center gap-2">
                    <span class="text-xs text-base-content/50">Cấp độ</span>
                    <span class="badge badge-outline badge-sm">{{ $cert->definition?->level_code ?? '—' }}</span>
                </div>
                <div class="flex justify-between items-center gap-2">
                    <span class="text-xs text-base-content/50">Ngày cấp</span>
                    <span class="text-sm">{{ $cert->issued_at?->format('d/m/Y') ?? '—' }}</span>
                </div>
                <div class="flex justify-between items-center gap-2">
                    <span class="text-xs text-base-content/50">Ngày hết hạn</span>
                    <span class="text-sm {{ $isExpired ? 'text-error font-semibold' : '' }}">
                        {{ $cert->expires_at?->format('d/m/Y') ?? 'Không có hạn' }}
                    </span>
                </div>
            </div>

            <div class="divider my-2"></div>

            {{-- QR + verify note --}}
            <div class="flex items-center gap-4">
                <div id="qr-cert" class="shrink-0"></div>
                <div class="text-xs text-base-content/40 leading-relaxed">
                    Quét mã QR hoặc truy cập trang này để xác minh tính hợp lệ của chứng nhận.<br>
                    <span class="font-mono break-all">{{ url()->current() }}</span>
                </div>
            </div>

            <div class="mt-4 text-center text-xs text-base-content/30">
                Được cấp bởi hệ thống Digital Workforce Framework (TDWCF)
            </div>

        </div>
    </div>
</div>

@push('scripts')
@vite(['resources/js/modules/qrcode.js'], 'build/backend')
<script>
document.addEventListener('DOMContentLoaded', () => {
    if (window.generateQR) {
        window.generateQR('{{ url()->current() }}', '#qr-cert', { size: 100 });
    }
});
</script>
@endpush

@endsection
