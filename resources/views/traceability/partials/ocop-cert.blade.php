@php($rows = array_filter($rows ?? []))
@php($until = $expiry ? \Illuminate\Support\Carbon::createFromFormat('d/m/Y', $expiry['label'])->endOfDay() : null)
@php($daysLeft = $until ? (int) floor(now()->diffInDays($until, false)) : null)
@php($state = match (true) {
    ! $until => 'unknown',
    $daysLeft < 0 => 'expired',
    $daysLeft <= 90 => 'soon',
    default => 'valid',
})
@php($remain = $daysLeft !== null && $daysLeft >= 0 ? ($daysLeft >= 60 ? __('còn :count tháng', ['count' => intdiv($daysLeft, 30)]) : __('còn :count ngày', ['count' => max(1, $daysLeft)])) : null)
@php($star = 'M10 1.6l2.47 5.01 5.53.8-4 3.9.94 5.5L10 14.2l-4.94 2.6.94-5.5-4-3.9 5.53-.8L10 1.6z')
<section class="mt-4 rounded-xl border border-[#ead9a8] overflow-hidden" aria-label="{{ __('Thông tin chứng nhận OCOP') }}">
    <div class="relative flex items-center gap-3 px-3.5 py-3 bg-[radial-gradient(120%_140%_at_0%_0%,#fffaf0_0%,#fbf0d2_70%,#f7e7bd_100%)]">
        <svg viewBox="0 0 48 56" @class(['shrink-0 w-9 h-[42px] sm:w-10 sm:h-12 drop-shadow-[0_1px_1.5px_rgb(120_80_0/.25)]', 'grayscale opacity-60' => ! $stars || $state === 'expired']) aria-hidden="true">
            <path d="M14 32 8 54l8-4 5 6 5-20z" fill="#b45309"/>
            <path d="M34 32l6 22-8-4-5 6-5-20z" fill="#c2410c"/>
            <circle cx="24" cy="22" r="20" fill="#e9b949"/>
            <circle cx="24" cy="22" r="16.5" fill="#fdf3d0" stroke="#d4a017" stroke-width="1.2"/>
            <text x="24" y="20.5" text-anchor="middle" font-size="7" font-weight="800" letter-spacing=".6" fill="#a16207" font-family="Inter Variable, Inter, system-ui, sans-serif">OCOP</text>
            <text x="24" y="31.5" text-anchor="middle" font-size="11" font-weight="800" fill="#422006" font-family="Inter Variable, Inter, system-ui, sans-serif">{{ $stars ?: '–' }}★</text>
        </svg>
        <div class="flex-1 min-w-0">
            @if ($stars)
                <p class="text-[clamp(14.5px,4vw,16px)] font-semibold leading-snug tracking-[-0.01em] text-[#422006] [text-wrap:balance]">@php($certText = $certTitle ?? __('Sản phẩm đạt chuẩn OCOP')){{ \Illuminate\Support\Str::beforeLast($certText, ' ') }} <span class="whitespace-nowrap">{{ \Illuminate\Support\Str::afterLast($certText, ' ') }} {{ $stars }} sao</span></p>
                <div class="mt-1 flex items-center gap-1.5">
                    <span class="flex gap-0.5" role="img" aria-label="{{ __('Hạng :stars trên 5 sao', ['stars' => $stars]) }}">
                        @for ($i = 1; $i <= 5; $i++)
                            @if ($i <= $stars)
                                <svg viewBox="0 0 20 20" @class(['w-4 h-4', 'text-[#e0a100]' => $state !== 'expired', 'text-[#a8a29e]' => $state === 'expired']) fill="currentColor" aria-hidden="true"><path d="{{ $star }}"/></svg>
                            @else
                                <svg viewBox="0 0 20 20" class="w-4 h-4 text-[#d9c58f]" fill="none" stroke="currentColor" stroke-width="1.4" stroke-linejoin="round" aria-hidden="true"><path d="{{ $star }}"/></svg>
                            @endif
                        @endfor
                    </span>
                    <span class="text-[11.5px] font-semibold text-[#a16207] tabular-nums">{{ $stars }}/5</span>
                </div>
            @else
                <p class="text-[clamp(14.5px,4vw,16px)] font-semibold leading-snug text-[#57534e]">{{ __('Chưa xếp hạng OCOP') }}</p>
            @endif
        </div>
    </div>
    @if ($stars && $state !== 'unknown')
        <div @class([
            'flex items-center gap-2 px-3.5 py-1.5 border-t text-[12.5px] leading-snug',
            'border-[#cfe9d8] bg-[#f0faf3] text-[#166534]' => $state === 'valid',
            'border-[#fde68a] bg-[#fffbeb] text-[#92400e]' => $state === 'soon',
            'border-[#fecaca] bg-[#fef2f2] text-[#b91c1c]' => $state === 'expired',
        ])>
            <span @class(['w-2 h-2 rounded-full shrink-0', 'bg-[#16a34a]' => $state === 'valid', 'bg-[#f59e0b]' => $state === 'soon', 'bg-[#dc2626]' => $state === 'expired']) aria-hidden="true"></span>
            <span class="flex-1 min-w-0">
                @switch($state)
                    @case('valid')
                        {{ __('Còn hiệu lực đến') }} <strong class="font-semibold tabular-nums">{{ $expiry['label'] }}</strong><span class="opacity-75"> · {{ $remain }}</span>
                        @break
                    @case('soon')
                        {{ __('Sắp hết hiệu lực:') }} <strong class="font-semibold tabular-nums">{{ $expiry['label'] }}</strong> · {{ $remain }}
                        @break
                    @default
                        {{ __('Chứng nhận đã hết hiệu lực từ') }} <strong class="font-semibold tabular-nums">{{ $expiry['label'] }}</strong>
                @endswitch
            </span>
        </div>
    @endif
    @if ($rows)
        <dl class="grid grid-cols-2 border-t border-[#ead9a8] bg-base-100">
            @foreach ($rows as $label => $value)
                <div @class(['px-3.5 py-2 min-w-0', 'col-span-2' => $loop->last && $loop->count % 2 === 1, 'border-l border-[var(--tx-line)]' => $loop->iteration % 2 === 0, 'border-t border-[var(--tx-line)]' => $loop->iteration > 2])>
                    <dt class="text-[11.5px] text-[#78716c]">{{ $label }}</dt>
                    <dd class="mt-0.5 text-[13.5px] font-semibold leading-snug [overflow-wrap:anywhere]">{{ $value }}</dd>
                </div>
            @endforeach
        </dl>
    @endif
    @if (! empty($docs))
        @include('traceability.partials.checklist', ['items' => $docs, 'label' => $docsLabel ?? __('Hồ sơ chất lượng')])
    @endif
</section>
