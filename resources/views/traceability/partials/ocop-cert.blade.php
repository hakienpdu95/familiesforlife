@php($rows = array_filter($rows ?? []))
<section class="mt-4 rounded-lg border border-[#ead9a8] overflow-hidden" aria-label="Thông tin chứng nhận OCOP">
    <div class="flex items-center gap-3 px-3.5 py-3 bg-[#fdf8ea]">
        @if ($stars)
            <span class="flex flex-col gap-0.5 min-w-0 flex-1">
                <span class="text-[15px] leading-none tracking-[0.06em]" aria-hidden="true"><span class="text-[#d4a017]">{{ str_repeat('★', $stars) }}</span><span class="text-[#e8dbb5]">{{ str_repeat('★', max(0, 5 - $stars)) }}</span></span>
                <span class="text-[16px] font-bold tracking-[-0.01em] text-[#422006]">Đạt chuẩn OCOP {{ $stars }} sao</span>
            </span>
        @else
            <span class="flex-1 text-[15px] font-semibold text-[#57534e]">Chưa có hạng OCOP</span>
        @endif
        <span @class([
            'shrink-0 inline-flex items-center gap-1.5 h-7 px-2 rounded-md text-[12.5px] font-semibold',
            'bg-[#e8f6ed] text-[#15803d]' => $expiry && $expiry['valid'],
            'bg-[#fdeaea] text-[#b91c1c]' => $expiry && ! $expiry['valid'],
            'bg-white/70 text-[#78716c]' => ! $expiry,
        ])>
            @if ($expiry)
                {{ $expiry['valid'] ? 'Hiệu lực đến '.$expiry['label'] : 'Hết hạn '.$expiry['label'] }}
            @else
                Hiệu lực: chưa cập nhật
            @endif
        </span>
    </div>
    @if ($rows)
        <dl class="grid grid-cols-2 border-t border-[#ead9a8] bg-base-100">
            @foreach ($rows as $label => $value)
                <div @class(['px-3.5 py-2.5 min-w-0', 'col-span-2' => $loop->last && $loop->count % 2 === 1, 'border-l border-[var(--tx-line)]' => $loop->iteration % 2 === 0, 'border-t border-[var(--tx-line)]' => $loop->iteration > 2])>
                    <dt class="text-[12px] text-[#78716c]">{{ $label }}</dt>
                    <dd class="mt-0.5 text-[14px] font-semibold leading-snug break-words">{{ $value }}</dd>
                </div>
            @endforeach
        </dl>
    @endif
    @if (! empty($docs))
        @include('traceability.partials.checklist', ['items' => $docs, 'label' => $docsLabel ?? 'Hồ sơ chất lượng'])
    @endif
</section>
