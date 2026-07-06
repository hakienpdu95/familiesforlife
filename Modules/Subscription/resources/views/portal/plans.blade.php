@extends('layouts.backend')

@section('title', 'Chọn gói dịch vụ')

@section('content')
<div class="p-6 max-w-5xl mx-auto space-y-6">

    @if(session('success'))
    <div class="alert alert-success">{{ session('success') }}</div>
    @endif
    @if(session('error'))
    <div class="alert alert-error">{{ session('error') }}</div>
    @endif

    <div class="text-center space-y-2">
        <h1 class="text-3xl font-bold">Chọn gói phù hợp</h1>
        @if($plan)
        <p class="text-base-content/60">Gói hiện tại: <strong>{{ $plan->name }}</strong></p>
        @endif
    </div>

    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
        @foreach($plans as $p)
        @php
            $isCurrent = $subscription && $subscription->plan_id === $p->id;
            $isUpgrade = $subscription && $p->price > ($plan->price ?? 0) && !$isCurrent;
            $isDowngrade = $subscription && $p->price < ($plan->price ?? 0) && !$isCurrent && $p->price > 0;
        @endphp
        <div class="card bg-base-100 shadow {{ $isCurrent ? 'ring-2 ring-primary' : '' }}">
            <div class="card-body">
                @if($p->badge_color && $p->tag_line)
                <div class="badge" style="background-color: {{ $p->badge_color }}; color: white;">{{ $p->tag_line }}</div>
                @endif

                <h2 class="card-title">{{ $p->name }}</h2>

                <div class="text-3xl font-bold">
                    @if($p->isFree())
                        Miễn phí
                    @else
                        {{ number_format($p->price, 0, ',', '.') }}
                        <span class="text-base font-normal text-base-content/60">{{ $p->currency }}/tháng</span>
                    @endif
                </div>

                @if($p->description)
                <p class="text-sm text-base-content/60">{{ $p->description }}</p>
                @endif

                @if($p->trial_period > 0 && !$subscription)
                <p class="text-sm text-info">Dùng thử miễn phí {{ $p->trial_period }} ngày</p>
                @endif

                {{-- Features --}}
                <ul class="space-y-1 text-sm my-2">
                    @foreach($p->features->where('value', '1') as $feature)
                    <li class="flex items-center gap-2">
                        <svg class="w-4 h-4 text-success" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z" clip-rule="evenodd"/></svg>
                        {{ $feature->name }}
                    </li>
                    @endforeach
                    @foreach($p->features->where('value', '!=', '1')->where('value', '!=', '0') as $feature)
                    <li class="flex items-center gap-2 text-base-content/70">
                        <svg class="w-4 h-4 text-base-content/40" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm1-11a1 1 0 10-2 0v2H7a1 1 0 100 2h2v2a1 1 0 102 0v-2h2a1 1 0 100-2h-2V7z" clip-rule="evenodd"/></svg>
                        {{ $feature->name }}: {{ $feature->value === '0' ? 'Không giới hạn' : $feature->value }}
                    </li>
                    @endforeach
                </ul>

                <div class="card-actions justify-end mt-auto">
                    @if($isCurrent)
                        <span class="btn btn-disabled btn-sm w-full">Gói hiện tại</span>
                    @elseif(!$subscription || $subscription->ended())
                        <form method="POST" action="{{ route('subscription.portal.subscribe') }}">
                            @csrf
                            <input type="hidden" name="plan_id" value="{{ $p->id }}">
                            <button class="btn btn-primary btn-sm w-full">Đăng ký</button>
                        </form>
                    @elseif($isUpgrade)
                        <form method="POST" action="{{ route('subscription.portal.upgrade') }}">
                            @csrf
                            <input type="hidden" name="plan_id" value="{{ $p->id }}">
                            <button class="btn btn-success btn-sm w-full">Nâng cấp</button>
                        </form>
                    @elseif($isDowngrade)
                        <form method="POST" action="{{ route('subscription.portal.downgrade') }}"
                              x-data
                              @submit.prevent="if(confirm('Hạ cấp plan sẽ mất một số tính năng. Tiếp tục?')) $el.submit()">
                            @csrf
                            <input type="hidden" name="plan_id" value="{{ $p->id }}">
                            <button class="btn btn-warning btn-outline btn-sm w-full">Hạ cấp</button>
                        </form>
                    @endif
                </div>
            </div>
        </div>
        @endforeach
    </div>

    <div class="text-center">
        <a href="{{ route('subscription.portal.billing') }}" class="btn btn-ghost btn-sm">← Quay lại Billing</a>
    </div>

</div>
@endsection
