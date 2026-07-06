@extends('layouts.backend')
@section('title', 'Chi tiết đơn nghỉ phép')


@section('content')
<div class="max-w-2xl">
    @if(session('success'))
    <div class="alert alert-success mb-4"><span>{{ session('success') }}</span></div>
    @endif
    @if($errors->any())
    <div class="alert alert-error mb-4">
        <ul class="list-disc list-inside text-sm">@foreach($errors->all() as $e)<li>{{ $e }}</li>@endforeach</ul>
    </div>
    @endif

    <div class="flex items-start justify-between mb-5">
        <div>
            <h1 class="text-2xl font-bold">Đơn nghỉ phép</h1>
            <p class="text-sm text-base-content/50 mt-0.5">{{ $leaveRequest->leave_type->label() }} · {{ $leaveRequest->days_count }} ngày</p>
        </div>
        <span class="badge badge-lg {{ $leaveRequest->status->badgeClass() }}">
            {{ $leaveRequest->status->label() }}
        </span>
    </div>

    <div class="card bg-base-100 shadow-sm border border-base-200 mb-4">
        <div class="card-body">
            <h2 class="card-title text-base mb-3">Thông tin đơn</h2>
            <dl class="grid grid-cols-2 gap-x-6 gap-y-3 text-sm">
                <div>
                    <dt class="text-base-content/50">Nhân viên</dt>
                    <dd class="font-medium">{{ $leaveRequest->employee?->full_name }} ({{ $leaveRequest->employee?->employee_code }})</dd>
                </div>
                <div>
                    <dt class="text-base-content/50">Phòng ban</dt>
                    <dd>{{ $leaveRequest->employee?->department?->name ?? '—' }}</dd>
                </div>
                <div>
                    <dt class="text-base-content/50">Loại nghỉ</dt>
                    <dd>{{ $leaveRequest->leave_type->label() }}</dd>
                </div>
                <div>
                    <dt class="text-base-content/50">Số ngày</dt>
                    <dd class="font-semibold tabular-nums">{{ $leaveRequest->days_count }} ngày</dd>
                </div>
                <div>
                    <dt class="text-base-content/50">Từ ngày</dt>
                    <dd class="tabular-nums">{{ $leaveRequest->date_from->format('d/m/Y') }}</dd>
                </div>
                <div>
                    <dt class="text-base-content/50">Đến ngày</dt>
                    <dd class="tabular-nums">{{ $leaveRequest->date_to->format('d/m/Y') }}</dd>
                </div>
                @if($leaveRequest->reason)
                <div class="col-span-2">
                    <dt class="text-base-content/50">Lý do</dt>
                    <dd class="mt-1">{{ $leaveRequest->reason }}</dd>
                </div>
                @endif
                @if($leaveRequest->attachment_url)
                <div class="col-span-2">
                    <dt class="text-base-content/50">Đính kèm</dt>
                    <dd><a href="{{ $leaveRequest->attachment_url }}" target="_blank" class="link link-primary">Xem tệp</a></dd>
                </div>
                @endif
            </dl>
        </div>
    </div>

    @if($leaveRequest->balance)
    <div class="card bg-base-100 shadow-sm border border-base-200 mb-4">
        <div class="card-body">
            <h2 class="card-title text-base mb-3">Số dư nghỉ phép ({{ now()->year }})</h2>
            <div class="grid grid-cols-4 gap-3 text-center text-sm">
                @php $b = $leaveRequest->balance; @endphp
                <div class="stat-value-sm">
                    <div class="text-2xl font-bold">{{ $b->entitled_days }}</div>
                    <div class="text-xs text-base-content/50">Được phép</div>
                </div>
                <div>
                    <div class="text-2xl font-bold text-success">{{ $b->used_days }}</div>
                    <div class="text-xs text-base-content/50">Đã dùng</div>
                </div>
                <div>
                    <div class="text-2xl font-bold text-warning">{{ $b->pending_days }}</div>
                    <div class="text-xs text-base-content/50">Đang chờ</div>
                </div>
                <div>
                    <div class="text-2xl font-bold text-info">{{ $b->remaining_days }}</div>
                    <div class="text-xs text-base-content/50">Còn lại</div>
                </div>
            </div>
        </div>
    </div>
    @endif

    @if($leaveRequest->status->value === 'approved' || $leaveRequest->status->value === 'rejected')
    <div class="card bg-base-100 shadow-sm border border-base-200 mb-4">
        <div class="card-body">
            <h2 class="card-title text-base mb-3">Kết quả xử lý</h2>
            <dl class="grid grid-cols-2 gap-x-6 gap-y-2 text-sm">
                <div>
                    <dt class="text-base-content/50">Người xử lý</dt>
                    <dd>{{ $leaveRequest->approvedBy?->full_name ?? '—' }}</dd>
                </div>
                <div>
                    <dt class="text-base-content/50">Thời gian</dt>
                    <dd class="tabular-nums">{{ $leaveRequest->approved_at?->format('d/m/Y H:i') ?? '—' }}</dd>
                </div>
                @if($leaveRequest->rejected_reason)
                <div class="col-span-2">
                    <dt class="text-base-content/50">Lý do từ chối</dt>
                    <dd class="mt-1 text-error">{{ $leaveRequest->rejected_reason }}</dd>
                </div>
                @endif
            </dl>
        </div>
    </div>
    @endif

    {{-- Actions --}}
    <div class="flex flex-wrap gap-3">
        @if($leaveRequest->isPending())
            @can('approve', $leaveRequest)
            <form method="POST" action="{{ route('backend.leave.requests.approve', $leaveRequest) }}">
                @csrf
                <button class="btn btn-success btn-sm">Duyệt đơn</button>
            </form>

            <div x-data="{ open: false }">
                <button @click="open = true" class="btn btn-error btn-sm">Từ chối</button>
                <div x-show="open" class="fixed inset-0 bg-black/40 z-50 flex items-center justify-center" x-cloak>
                    <div class="card bg-base-100 w-96 shadow-xl">
                        <div class="card-body">
                            <h3 class="card-title text-base">Lý do từ chối</h3>
                            <form method="POST" action="{{ route('backend.leave.requests.reject', $leaveRequest) }}">
                                @csrf
                                <textarea name="rejected_reason" rows="3" class="textarea textarea-bordered w-full mb-3"
                                          placeholder="Nhập lý do từ chối..." required></textarea>
                                <div class="flex gap-2 justify-end">
                                    <button type="button" @click="open = false" class="btn btn-ghost btn-sm">Hủy</button>
                                    <button type="submit" class="btn btn-error btn-sm">Xác nhận từ chối</button>
                                </div>
                            </form>
                        </div>
                    </div>
                </div>
            </div>
            @endcan

            @can('cancel', $leaveRequest)
            <form method="POST" action="{{ route('backend.leave.requests.cancel', $leaveRequest) }}"
                  onsubmit="return confirm('Hủy đơn nghỉ này?')">
                @csrf
                <button class="btn btn-ghost btn-sm">Hủy đơn</button>
            </form>
            @endcan
        @endif

        <a href="{{ route('backend.leave.requests.index') }}" class="btn btn-ghost btn-sm">Quay lại</a>
    </div>
</div>
@endsection
