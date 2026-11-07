@if ($facts)
    <dl class="rounded-lg bg-base-200 divide-y divide-base-300 text-sm">
        @foreach ($facts as [$label, $value])
            <div class="flex justify-between gap-4 px-3 py-2.5">
                <dt class="text-base-content/60 shrink-0">{{ $label }}</dt>
                <dd class="font-medium text-right break-words">{{ $value }}</dd>
            </div>
        @endforeach
    </dl>
@endif
