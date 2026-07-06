@extends('layouts.backend')
@section('title', 'Số dư nghỉ phép — ' . $employee->full_name)


@section('content')
<div class="max-w-3xl">
    <div class="flex items-center justify-between mb-6">
        <div>
            <h1 class="text-2xl font-bold">{{ $employee->full_name }}</h1>
            <p class="text-sm text-base-content/50 mt-0.5">Số dư nghỉ phép năm {{ $year }}</p>
        </div>
        <form method="GET" class="flex items-center gap-2">
            <select name="year" class="select select-bordered select-sm w-28" onchange="this.form.submit()">
                @for($y = now()->year; $y >= now()->year - 3; $y--)
                <option value="{{ $y }}" @selected($y === $year)>{{ $y }}</option>
                @endfor
            </select>
        </form>
    </div>

    @if($balances->isEmpty())
    <div class="alert alert-info">
        <span>Chưa có số dư nghỉ phép nào cho năm {{ $year }}.</span>
    </div>
    @else
    <div class="card bg-base-100 shadow-sm border border-base-200">
        <div class="card-body p-0">
            <table class="table w-full">
                <thead>
                    <tr class="text-xs text-base-content/60 uppercase">
                        <th>Loại nghỉ</th>
                        <th class="text-center">Được phép</th>
                        <th class="text-center">Chuyển tiếp</th>
                        <th class="text-center">Điều chỉnh</th>
                        <th class="text-center">Đã dùng</th>
                        <th class="text-center">Đang chờ</th>
                        <th class="text-center font-bold">Còn lại</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($balances as $balance)
                    <tr>
                        <td>
                            <div class="font-medium">{{ $balance->leave_type->label() }}</div>
                            <div class="text-xs text-base-content/50">{{ $balance->policy?->name }}</div>
                        </td>
                        <td class="text-center tabular-nums">{{ $balance->entitled_days }}</td>
                        <td class="text-center tabular-nums">{{ $balance->carried_over }}</td>
                        <td class="text-center tabular-nums">
                            @if($balance->adjusted > 0)
                                <span class="text-success">+{{ $balance->adjusted }}</span>
                            @elseif($balance->adjusted < 0)
                                <span class="text-error">{{ $balance->adjusted }}</span>
                            @else
                                0
                            @endif
                        </td>
                        <td class="text-center tabular-nums text-success font-medium">{{ $balance->used_days }}</td>
                        <td class="text-center tabular-nums text-warning">{{ $balance->pending_days }}</td>
                        <td class="text-center tabular-nums font-bold text-info">{{ $balance->remaining_days }}</td>
                    </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    </div>
    @endif
</div>
@endsection
