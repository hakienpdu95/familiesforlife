@php($facts = array_values(array_filter($facts)))
@if ($facts)
    <section class="px-4 mt-5">
        @isset($heading)<h2 class="text-sm font-bold mb-2">{{ $heading }}</h2>@endisset
        <dl class="grid grid-cols-2 gap-2">
            @foreach ($facts as $fact)
                <div @class(['rounded-xl bg-base-200 px-3 py-2.5 flex items-start gap-2.5', 'col-span-2' => mb_strlen($fact['value']) > 28])>
                    <span class="flex items-center justify-center w-8 h-8 rounded-lg bg-primary/10 text-primary shrink-0">
                        <i class="fa-solid {{ $fact['icon'] }}" aria-hidden="true"></i>
                    </span>
                    <div class="min-w-0">
                        <dt class="text-[11px] text-base-content/50">{{ $fact['label'] }}</dt>
                        <dd class="text-sm font-semibold break-words">{{ $fact['value'] }}</dd>
                    </div>
                </div>
            @endforeach
        </dl>
    </section>
@endif
