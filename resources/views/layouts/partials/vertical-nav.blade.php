{{--
    Render sidebar_config của một vertical.
    Props: $vertical (VerticalDefinition), $currentVerticalCode (string|null)

    Route naming convention: sidebar_config dùng "{vertical}.dashboard" →
    thực tế là "deployment.dashboard" + param ['vertical' => $code].
    Items thiếu route hoặc cần thêm param context sẽ bị bỏ qua tự động.
--}}
@php
    $code       = $vertical->code();
    $isActive   = $currentVerticalCode === $code;
    $routeParam = ['vertical' => $code];
@endphp

<details {{ $isActive ? 'open' : '' }} class="vertical-group">
    <summary class="nav-summary {{ $isActive ? 'active' : '' }}">
        <svg class="nav-icon" fill="none" viewBox="0 0 24 24" stroke="currentColor">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8"
                  d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2"/>
        </svg>
        <span class="nav-label">{{ $vertical->label() }}</span>
        <svg class="nav-arrow" fill="none" viewBox="0 0 24 24" stroke="currentColor">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="m9 18 6-6-6-6"/>
        </svg>
    </summary>

    <div class="sub-menu">
        @foreach($vertical->sidebarGroups() as $groupTitle => $items)
            <p class="sub-section-title">{{ $groupTitle }}</p>
            @foreach($items as $item)
                @php
                    // {vertical} → deployment (module prefix), {target} → vertical label
                    $routeName = str_replace('{vertical}', 'deployment', $item['route']);
                    $label     = str_replace('{target}', $vertical->targetLabel(), $item['label']);
                    $url       = null;
                    if (\Illuminate\Support\Facades\Route::has($routeName)) {
                        try { $url = route($routeName, $routeParam); } catch (\Throwable) {}
                    }
                @endphp
                @if($url)
                    <a href="{{ $url }}"
                       class="sub-link {{ request()->routeIs($routeName) ? 'active' : '' }}">
                        {{ $label }}
                    </a>
                @endif
            @endforeach
        @endforeach
    </div>
</details>
