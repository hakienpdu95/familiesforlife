@extends('layouts.backend')

@section('title', 'Chuyển khoản ngân hàng')

@section('content')
<div class="p-6 max-w-lg mx-auto space-y-6">

    <div class="card bg-base-100 shadow">
        <div class="card-body space-y-5">
            <div class="flex items-center gap-3">
                <div class="w-10 h-10 bg-info/10 rounded-full flex items-center justify-center">
                    <svg class="w-5 h-5 text-info" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                              d="M3 10h18M7 15h1m4 0h1m-7 4h12a3 3 0 003-3V8a3 3 0 00-3-3H6a3 3 0 00-3 3v8a3 3 0 003 3z"/>
                    </svg>
                </div>
                <h2 class="card-title">Thông tin chuyển khoản</h2>
            </div>

            <div class="alert alert-info text-sm">
                Hệ thống sẽ tự động xác nhận sau khi nhận được chuyển khoản. Vui lòng điền đúng nội dung chuyển khoản.
            </div>

            <div class="space-y-3">
                @php $fields = [
                    'Ngân hàng'     => $bank_name,
                    'Số tài khoản'  => $account_number,
                    'Chủ tài khoản' => $account_name,
                    'Số tiền'       => number_format($invoice->amount, 0, ',', '.') . ' VND',
                ] @endphp

                @foreach($fields as $label => $value)
                <div class="flex justify-between items-center py-2 border-b border-base-200">
                    <span class="text-base-content/60 text-sm">{{ $label }}</span>
                    <span class="font-medium">{{ $value }}</span>
                </div>
                @endforeach

                {{-- Transfer description — most critical field --}}
                <div class="bg-warning/10 border border-warning/30 rounded-lg p-4 space-y-2">
                    <p class="text-sm font-semibold text-warning-content">Nội dung chuyển khoản (bắt buộc)</p>
                    <div class="flex items-center gap-2">
                        <code class="text-lg font-mono font-bold tracking-wide flex-1" id="transfer-code">{{ $invoice->invoice_number }}</code>
                        <button onclick="navigator.clipboard.writeText('{{ $invoice->invoice_number }}');this.textContent='✓ Đã copy'"
                                class="btn btn-xs btn-ghost border border-base-300">
                            Copy
                        </button>
                    </div>
                    <p class="text-xs text-base-content/50">
                        Hệ thống ghép lệnh chuyển khoản qua nội dung này. Sai nội dung sẽ không được xác nhận tự động.
                    </p>
                </div>
            </div>

            <div class="text-sm text-base-content/50 space-y-1">
                <p>Mã hóa đơn: <strong>{{ $invoice->invoice_number }}</strong></p>
                <p>Hạn thanh toán: <strong>{{ $invoice->due_date?->format('d/m/Y') ?? 'Không giới hạn' }}</strong></p>
            </div>

            <div class="card-actions">
                <a href="{{ route('subscription.portal.billing') }}" class="btn btn-ghost btn-sm">
                    Quay lại Billing
                </a>
            </div>
        </div>
    </div>

</div>
@endsection
