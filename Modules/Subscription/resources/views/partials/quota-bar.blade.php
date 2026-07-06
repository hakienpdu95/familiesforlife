{{--
  quota-bar.blade.php — reusable component for displaying quota/limit usage.

  Usage:
    @include('subscription::partials.quota-bar', [
        'label'   => 'Nhân viên',
        'used'    => $current,
        'limit'   => org_limit('limit.employees'),   // 0 = unlimited
        'slug'    => 'limit.employees',
    ])

  Props:
    $label  — display name
    $used   — current count
    $limit  — max allowed (0 = unlimited)
    $slug   — feature slug (optional, for styling)
--}}
@php
    $unlimited = ($limit ?? 0) === 0;
    $pct       = (!$unlimited && $limit > 0) ? min(100, round($used / $limit * 100)) : 0;
    $color     = $pct >= 90 ? 'progress-error' : ($pct >= 70 ? 'progress-warning' : 'progress-success');
@endphp

<div class="flex flex-col gap-1 text-sm">
    <div class="flex justify-between items-center">
        <span class="font-medium text-base-content/80">{{ $label }}</span>
        <span class="text-base-content/60 tabular-nums">
            @if($unlimited)
                {{ number_format($used) }} / <span class="text-success font-semibold">∞</span>
            @else
                {{ number_format($used) }} / {{ number_format($limit) }}
            @endif
        </span>
    </div>

    @if(!$unlimited)
        <progress
            class="progress {{ $color }} h-2 w-full"
            value="{{ $pct }}"
            max="100"
        ></progress>

        @if($pct >= 90)
            <p class="text-error text-xs">
                Sắp đạt giới hạn. <a href="{{ route('subscription.portal.plans') }}" class="link">Nâng cấp</a> để tiếp tục.
            </p>
        @endif
    @else
        <div class="h-2 rounded-full bg-success/20"></div>
    @endif
</div>
