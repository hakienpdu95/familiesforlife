@php($rows = array_values(array_filter($rows)))
@if ($rows)
    <dl class="tx-card tx-rows text-[15px]">
        @foreach ($rows as $row)
            @php($long = mb_strlen((string) $row['value']) > 34)
            <div @class(['px-4 py-3', 'flex items-baseline justify-between gap-4' => ! $long])>
                <dt @class(['text-base-content/55 shrink-0', 'text-[13px] mb-0.5' => $long])>{{ $row['label'] }}</dt>
                <dd @class(['font-medium break-words min-w-0', 'text-right' => ! $long])>{{ $row['value'] }}</dd>
            </div>
        @endforeach
    </dl>
@endif
