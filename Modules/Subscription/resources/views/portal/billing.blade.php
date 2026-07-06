@extends('layouts.backend')

@section('title', 'Billing & Subscription')

@section('content')
<div class="p-6 max-w-4xl mx-auto space-y-6">

    @if(session('success'))
    <div class="alert alert-success">{{ session('success') }}</div>
    @endif

    @if(session('error'))
    <div class="alert alert-error">{{ session('error') }}</div>
    @endif

    {{-- Current Plan Card --}}
    <div class="card bg-base-100 shadow">
        <div class="card-body">
            <h2 class="card-title">Subscription hiện tại</h2>

            @if($subscription)
            <div class="flex flex-wrap gap-4 items-start">
                <div class="flex-1 space-y-2">
                    <div class="text-2xl font-bold">{{ $plan->name ?? '—' }}</div>

                    <div class="flex gap-2 flex-wrap">
                        @if($is_trial)
                            <span class="badge badge-info">Đang dùng thử</span>
                        @elseif($is_active)
                            <span class="badge badge-success">Đang hoạt động</span>
                        @elseif($is_canceled)
                            <span class="badge badge-warning">Đã hủy</span>
                        @elseif($is_ended)
                            <span class="badge badge-error">Đã hết hạn</span>
                        @endif
                    </div>

                    @if($is_trial && $trial_ends_at)
                    <p class="text-sm text-base-content/70">Dùng thử đến: <strong>{{ $trial_ends_at->format('d/m/Y') }}</strong></p>
                    @endif

                    @if($ends_at)
                    <p class="text-sm text-base-content/70">
                        {{ $is_canceled ? 'Kết thúc vào:' : 'Gia hạn vào:' }}
                        <strong>{{ $ends_at->format('d/m/Y') }}</strong>
                    </p>
                    @endif

                    @if($canceled_at && !$is_ended)
                    <p class="text-sm text-warning">Subscription sẽ kết thúc vào {{ $ends_at?->format('d/m/Y') }}.</p>
                    @endif
                </div>

                <div class="flex flex-col gap-2">
                    @if($is_canceled && !$is_ended)
                    <form method="POST" action="{{ route('subscription.portal.resume') }}">
                        @csrf
                        <button class="btn btn-success btn-sm">Khôi phục subscription</button>
                    </form>
                    @elseif($is_active || $is_trial)
                    <a href="{{ route('subscription.portal.plans') }}" class="btn btn-primary btn-sm">Đổi plan</a>
                    <form method="POST" action="{{ route('subscription.portal.cancel') }}"
                          x-data
                          @submit.prevent="if(confirm('Bạn có chắc muốn hủy subscription?')) $el.submit()">
                        @csrf
                        <button class="btn btn-error btn-outline btn-sm w-full">Hủy subscription</button>
                    </form>
                    @endif
                </div>
            </div>

            @else
            <div class="py-4 text-center space-y-3">
                <p class="text-base-content/60">Bạn chưa có subscription nào.</p>
                <a href="{{ route('subscription.portal.plans') }}" class="btn btn-primary">Xem các gói</a>
            </div>
            @endif
        </div>
    </div>

    {{-- Recent Changes --}}
    @if($recent_changes->isNotEmpty())
    <div class="card bg-base-100 shadow">
        <div class="card-body">
            <h2 class="card-title text-base">Lịch sử thay đổi</h2>
            <div class="overflow-x-auto">
                <table class="table table-sm">
                    <thead>
                        <tr>
                            <th>Thời gian</th>
                            <th>Loại</th>
                            <th>Từ plan</th>
                            <th>Sang plan</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($recent_changes as $change)
                        <tr>
                            <td class="text-sm">{{ $change->created_at?->format('d/m/Y H:i') }}</td>
                            <td>
                                <span class="badge badge-sm
                                    @switch($change->change_type->value)
                                        @case('upgrade') badge-success @break
                                        @case('downgrade') badge-warning @break
                                        @case('cancel') badge-error @break
                                        @case('resume') badge-info @break
                                        @default badge-ghost
                                    @endswitch
                                ">{{ $change->change_type->value }}</span>
                            </td>
                            <td class="text-sm">{{ $change->fromPlan?->name ?? '—' }}</td>
                            <td class="text-sm">{{ $change->toPlan?->name ?? '—' }}</td>
                        </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </div>
    </div>
    @endif

</div>
@endsection
