@php($total = count($items))
@php($done = collect($items)->filter(fn ($i) => $i['count'] > 0)->count())
<div class="tx-card">
    <div class="flex items-center gap-3 px-4 py-3 border-b border-[var(--tx-line)]">
        <div class="flex flex-1 gap-1" aria-hidden="true">
            @foreach ($items as $item)
                <span @class(['h-1.5 flex-1 rounded-full', 'bg-success' => $item['count'] > 0, 'bg-base-300' => $item['count'] === 0])></span>
            @endforeach
        </div>
        <span @class(['text-[13px] font-semibold tabular-nums shrink-0', 'text-success' => $done === $total, 'text-base-content/70' => $done < $total])>{{ $done }}/{{ $total }} đã nộp</span>
    </div>
    <ul class="tx-rows text-[15px]">
        @foreach ($items as $item)
            <li class="flex items-center gap-3 px-4 py-2.5">
                @if ($item['count'] > 0)
                    <span class="w-5 h-5 rounded-full bg-success/15 text-success flex items-center justify-center shrink-0" aria-hidden="true">
                        <svg viewBox="0 0 20 20" fill="currentColor" class="w-3.5 h-3.5"><path fill-rule="evenodd" d="M16.7 5.3a1 1 0 0 1 0 1.4l-7.5 7.5a1 1 0 0 1-1.4 0l-3.5-3.5a1 1 0 1 1 1.4-1.4l2.8 2.8 6.8-6.8a1 1 0 0 1 1.4 0Z" clip-rule="evenodd"/></svg>
                    </span>
                @else
                    <span class="w-5 h-5 rounded-full border-[1.5px] border-dashed border-base-content/25 shrink-0" aria-hidden="true"></span>
                @endif
                <span @class(['flex-1 min-w-0 leading-snug', 'text-base-content/60' => $item['count'] === 0])>{{ $item['label'] }}</span>
                <span @class(['text-[12px] shrink-0', 'text-success font-medium' => $item['count'] > 0, 'text-base-content/45' => $item['count'] === 0])>{{ $item['count'] > 0 ? ($item['updated_at'] ? $item['updated_at'] : 'Đã nộp') : 'Chưa có' }}</span>
            </li>
        @endforeach
    </ul>
</div>
