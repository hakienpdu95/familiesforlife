@extends('layouts.backend')
@section('title', 'Đơn nghỉ phép')


@section('content')
<div>
    <div class="flex flex-wrap items-center justify-between gap-3 mb-5">
        <div>
            <h1 class="text-2xl font-bold">Đơn nghỉ phép</h1>
            <p class="text-sm text-base-content/50 mt-0.5">Lịch sử và quản lý các đơn nghỉ phép</p>
        </div>
        <div class="flex gap-2">
            @can('approve', new \Modules\Leave\Models\LeaveRequest)
            <a href="{{ route('backend.leave.requests.pending') }}" class="btn btn-warning btn-sm gap-1.5">
                Chờ duyệt
            </a>
            @endcan
            <a href="{{ route('backend.leave.requests.create') }}" class="btn btn-primary btn-sm gap-1.5">
                <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/>
                </svg>
                Đăng ký nghỉ
            </a>
        </div>
    </div>

    @if(session('success'))
    <div class="alert alert-success mb-4"><span>{{ session('success') }}</span></div>
    @endif

    {{-- Filters --}}
    <div class="card bg-base-100 shadow-sm border border-base-200 mb-4">
        <div class="card-body py-3">
            <form method="GET" class="flex flex-wrap gap-3 items-end">
                <div class="form-control">
                    <label class="label py-1"><span class="label-text text-xs">Loại nghỉ</span></label>
                    <select name="leave_type" class="select select-bordered select-sm w-40">
                        <option value="">Tất cả</option>
                        @foreach($leaveTypes as $type)
                        <option value="{{ $type['value'] }}" @selected(request('leave_type') === $type['value'])>
                            {{ $type['text'] }}
                        </option>
                        @endforeach
                    </select>
                </div>
                <div class="form-control">
                    <label class="label py-1"><span class="label-text text-xs">Trạng thái</span></label>
                    <select name="status" class="select select-bordered select-sm w-36">
                        <option value="">Tất cả</option>
                        @foreach($statuses as $status)
                        <option value="{{ $status['value'] }}" @selected(request('status') === $status['value'])>
                            {{ $status['text'] }}
                        </option>
                        @endforeach
                    </select>
                </div>
                <div class="form-control">
                    <label class="label py-1"><span class="label-text text-xs">Từ ngày</span></label>
                    <input type="date" name="date_from" value="{{ request('date_from') }}"
                           class="input input-bordered input-sm w-36">
                </div>
                <div class="form-control">
                    <label class="label py-1"><span class="label-text text-xs">Đến ngày</span></label>
                    <input type="date" name="date_to" value="{{ request('date_to') }}"
                           class="input input-bordered input-sm w-36">
                </div>
                <button type="submit" class="btn btn-sm btn-outline">Lọc</button>
                <a href="{{ route('backend.leave.requests.index') }}" class="btn btn-sm btn-ghost">Xóa lọc</a>
            </form>
        </div>
    </div>

    {{-- List --}}
    <div class="card bg-base-100 shadow-sm border border-base-200">
        <div class="card-body p-0">
            <div class="overflow-x-auto">
                <table class="table table-zebra w-full">
                    <thead>
                        <tr class="text-xs text-base-content/60 uppercase">
                            <th>Nhân viên</th>
                            <th>Loại nghỉ</th>
                            <th>Từ ngày</th>
                            <th>Đến ngày</th>
                            <th class="text-center">Số ngày</th>
                            <th>Trạng thái</th>
                            <th>Người duyệt</th>
                            <th class="text-right">Chi tiết</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($requests as $req)
                        <tr>
                            <td>
                                <div class="font-medium text-sm">{{ $req->employee?->full_name }}</div>
                                <div class="text-xs text-base-content/50">{{ $req->employee?->employee_code }}</div>
                            </td>
                            <td><span class="badge badge-ghost badge-sm">{{ $req->leave_type->label() }}</span></td>
                            <td class="tabular-nums text-sm">{{ $req->date_from->format('d/m/Y') }}</td>
                            <td class="tabular-nums text-sm">{{ $req->date_to->format('d/m/Y') }}</td>
                            <td class="text-center tabular-nums font-medium">{{ $req->days_count }}</td>
                            <td>
                                <span class="badge badge-sm {{ $req->status->badgeClass() }}">
                                    {{ $req->status->label() }}
                                </span>
                            </td>
                            <td class="text-sm text-base-content/60">{{ $req->approvedBy?->full_name ?? '—' }}</td>
                            <td class="text-right">
                                <a href="{{ route('backend.leave.requests.show', $req) }}"
                                   class="btn btn-ghost btn-xs">Xem</a>
                            </td>
                        </tr>
                        @empty
                        <tr>
                            <td colspan="8" class="text-center py-10 text-base-content/40">
                                Không có đơn nghỉ nào.
                            </td>
                        </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
            @if($requests->hasPages())
            <div class="p-4 border-t border-base-200">
                {{ $requests->links() }}
            </div>
            @endif
        </div>
    </div>
</div>
@endsection
