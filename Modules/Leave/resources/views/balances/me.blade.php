@extends('layouts.backend')
@section('title', 'Số dư nghỉ phép của tôi')


@section('content')
<div class="max-w-3xl">
    <div class="flex items-center justify-between mb-6">
        <div>
            <h1 class="text-2xl font-bold">Số dư nghỉ phép của tôi</h1>
            <p class="text-sm text-base-content/50 mt-0.5">Năm {{ $year }}</p>
        </div>
        <a href="{{ route('backend.leave.requests.create') }}" class="btn btn-primary btn-sm">
            Đăng ký nghỉ
        </a>
    </div>

    @if(!$employee)
    <div class="alert alert-warning">
        <span>Tài khoản của bạn chưa được liên kết với hồ sơ nhân viên. Vui lòng liên hệ HR.</span>
    </div>
    @elseif($balances->isEmpty())
    <div class="alert alert-info">
        <span>Bạn chưa có số dư nghỉ phép nào cho năm {{ $year }}. Vui lòng liên hệ HR để khởi tạo.</span>
    </div>
    @else
    <div class="grid gap-4">
        @foreach($balances as $balance)
        <div class="card bg-base-100 shadow-sm border border-base-200">
            <div class="card-body">
                <div class="flex items-center justify-between mb-4">
                    <div>
                        <h3 class="font-semibold">{{ $balance->leave_type->label() }}</h3>
                        <div class="text-xs text-base-content/50">{{ $balance->policy?->name }}</div>
                    </div>
                    @php
                        $remaining = $balance->remaining_days;
                        $total = $balance->entitled_days + $balance->carried_over + $balance->adjusted;
                        $pct = $total > 0 ? min(100, (($balance->used_days + $balance->pending_days) / $total) * 100) : 0;
                    @endphp
                </div>

                <div class="grid grid-cols-5 gap-2 text-center text-sm mb-3">
                    <div class="bg-base-200 rounded-lg p-2">
                        <div class="text-lg font-bold tabular-nums">{{ $balance->entitled_days }}</div>
                        <div class="text-xs text-base-content/50">Được phép</div>
                    </div>
                    <div class="bg-base-200 rounded-lg p-2">
                        <div class="text-lg font-bold tabular-nums">{{ $balance->carried_over }}</div>
                        <div class="text-xs text-base-content/50">Chuyển tiếp</div>
                    </div>
                    <div class="bg-success/10 rounded-lg p-2">
                        <div class="text-lg font-bold text-success tabular-nums">{{ $balance->used_days }}</div>
                        <div class="text-xs text-base-content/50">Đã dùng</div>
                    </div>
                    <div class="bg-warning/10 rounded-lg p-2">
                        <div class="text-lg font-bold text-warning tabular-nums">{{ $balance->pending_days }}</div>
                        <div class="text-xs text-base-content/50">Đang chờ</div>
                    </div>
                    <div class="bg-info/10 rounded-lg p-2">
                        <div class="text-lg font-bold text-info tabular-nums">{{ $remaining }}</div>
                        <div class="text-xs text-base-content/50">Còn lại</div>
                    </div>
                </div>

                <div class="w-full bg-base-200 rounded-full h-2">
                    <div class="bg-primary h-2 rounded-full transition-all"
                         style="width: {{ $pct }}%"></div>
                </div>
            </div>
        </div>
        @endforeach
    </div>
    @endif
</div>
@endsection
