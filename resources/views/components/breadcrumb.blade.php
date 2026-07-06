@php
    /** @var array<int,array{label:string,url:string|null,active:bool}> $crumbs */
    $crumbs = app(\App\Support\Breadcrumbs::class)->generate();
@endphp

{{-- Only render when there is more than just the home crumb --}}
@if(count($crumbs) > 1)
<nav aria-label="breadcrumb" class="breadcrumbs text-sm px-6 pt-4 pb-0">
    <ul>
        @foreach($crumbs as $crumb)
            @if($crumb['active'])
                <li class="font-medium text-base-content" aria-current="page">
                    {{ $crumb['label'] }}
                </li>
            @elseif($crumb['url'])
                <li>
                    <a href="{{ $crumb['url'] }}" class="hover:text-primary transition-colors">
                        {{ $crumb['label'] }}
                    </a>
                </li>
            @else
                <li class="text-base-content/60">{{ $crumb['label'] }}</li>
            @endif
        @endforeach
    </ul>
</nav>
@endif
