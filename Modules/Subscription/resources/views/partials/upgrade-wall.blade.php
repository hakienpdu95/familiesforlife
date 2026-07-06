{{--
  upgrade-wall.blade.php
  Returned with HTTP 402 by RequireFeature middleware.
  Vars: $feature (string), $plan (?Plan), $upgradeUrl (string)
--}}
<!DOCTYPE html>
<html lang="vi" data-theme="light">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Nâng cấp gói để tiếp tục — {{ config('app.name') }}</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'], 'build/backend')
</head>
<body class="min-h-screen bg-base-200 flex items-center justify-center p-4">

<div class="card w-full max-w-lg bg-base-100 shadow-xl">
    <div class="card-body items-center text-center gap-4">

        {{-- Icon --}}
        <div class="text-warning">
            <svg xmlns="http://www.w3.org/2000/svg" class="w-16 h-16" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5"
                      d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z"/>
            </svg>
        </div>

        <h1 class="card-title text-2xl">Tính năng chưa khả dụng</h1>

        <p class="text-base-content/70 text-sm">
            Tính năng <code class="badge badge-ghost">{{ $feature }}</code>
            chưa có trong gói
            @if($plan)
                <strong>{{ $plan->name }}</strong>
            @else
                hiện tại
            @endif
            của bạn.
        </p>

        {{-- Plan comparison summary --}}
        <div class="divider text-xs text-base-content/50">Các gói có tính năng này</div>

        <div class="grid grid-cols-3 gap-2 w-full text-sm">
            @php
                $tiers = [
                    ['slug' => 'growth',     'name' => 'Growth',     'price' => '990K/tháng', 'badge' => 'badge-primary'],
                    ['slug' => 'scale',      'name' => 'Scale',      'price' => '2.49M/tháng','badge' => 'badge-success'],
                    ['slug' => 'enterprise', 'name' => 'Enterprise', 'price' => 'Liên hệ',    'badge' => 'badge-secondary'],
                ];
            @endphp
            @foreach($tiers as $tier)
            <div class="border border-base-300 rounded-lg p-3 flex flex-col items-center gap-1">
                <span class="badge {{ $tier['badge'] }} badge-sm">{{ $tier['name'] }}</span>
                <span class="font-semibold text-xs">{{ $tier['price'] }}</span>
            </div>
            @endforeach
        </div>

        <div class="card-actions mt-2 gap-2 flex-col w-full">
            <a href="{{ $upgradeUrl }}" class="btn btn-primary btn-block">
                Xem các gói &amp; nâng cấp
            </a>
            <button onclick="history.back()" class="btn btn-ghost btn-sm btn-block">
                ← Quay lại
            </button>
        </div>

    </div>
</div>

</body>
</html>
