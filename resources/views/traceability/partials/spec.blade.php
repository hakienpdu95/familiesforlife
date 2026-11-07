@php($rows = array_values(array_filter($rows)))
@if ($rows)
    <dl class="border-t border-base-300 text-[15px]">
        @foreach ($rows as $row)
            <div class="flex items-baseline justify-between gap-6 py-2.5 border-b border-base-300">
                <dt class="text-base-content/60 shrink-0">{{ $row['label'] }}</dt>
                <dd class="text-right font-medium break-words min-w-0">{{ $row['value'] }}</dd>
            </div>
        @endforeach
    </dl>
@endif
