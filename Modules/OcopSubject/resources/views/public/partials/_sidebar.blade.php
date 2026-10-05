@php
    $intro = \Illuminate\Support\Str::limit(trim(preg_replace('/\s+/u', ' ', strip_tags((string) $subject->story))), 220);
    $infoRows = collect([
        'Loại hình' => $subject->organization_type?->label(),
        'Hạng OCOP' => $subject->ocop_star ? $subject->ocop_star.' sao' : null,
        'Địa chỉ' => $subject->fullAddress(),
    ])->filter();
    $contacts = collect([
        'Hotline' => $subject->hotline ? ['tel:'.preg_replace('/[^0-9+]/', '', $subject->hotline), $subject->hotline, false] : null,
        'Email' => $subject->email ? ['mailto:'.$subject->email, $subject->email, false] : null,
        'Website' => $subject->website ? [$subject->website, preg_replace('#^https?://#', '', rtrim($subject->website, '/')), true] : null,
    ])->filter();
@endphp

<div class="overflow-hidden rounded-sm bg-[#f8f9fa] p-4 text-center">
    @if($gallery->isNotEmpty())
    <div class="mx-auto flex h-28 w-28 items-center justify-center overflow-hidden rounded-full border border-[#e0e0e0] bg-white">
        <img src="{{ $gallery[0]['thumb'] }}" alt="{{ $subject->name }}" class="h-full w-full object-cover">
    </div>
    @else
    <div class="mx-auto flex h-28 w-28 items-center justify-center rounded-full border border-[#e0e0e0] bg-white text-4xl font-bold text-[#117a3a]">
        {{ mb_strtoupper(mb_substr($subject->name, 0, 1)) }}
    </div>
    @endif
    <h2 class="mt-3 font-bold uppercase leading-snug text-[#117a3a]">{{ $subject->name }}</h2>
    @if($subject->ocop_star)
    <div class="mt-2 flex justify-center" title="Hạng {{ $subject->ocop_star }} sao OCOP">
        <div class="rating rating-xs pointer-events-none">
            @for($s = 1; $s <= 5; $s++)
            <div class="mask mask-star-2 {{ $s <= $subject->ocop_star ? 'bg-warning' : 'bg-base-300' }}"></div>
            @endfor
        </div>
    </div>
    @endif
</div>

<div class="overflow-hidden rounded-sm bg-[#f8f9fa]">
    <div class="border-b border-[#e0e0e0] px-4 py-3 text-base font-semibold text-[#333]">Thông tin thương hiệu</div>
    <div class="space-y-3 p-4 text-sm text-[#555]">
        @if($intro)
        <p class="leading-relaxed">{{ $intro }}</p>
        @endif
        @if($infoRows->isNotEmpty() || $contacts->isNotEmpty())
        <dl class="space-y-1.5">
            @foreach($infoRows as $label => $value)
            <div class="flex gap-2">
                <dt class="shrink-0 font-medium text-[#333]">{{ $label }}:</dt>
                <dd class="min-w-0">{{ $value }}</dd>
            </div>
            @endforeach
            @foreach($contacts as $label => [$href, $text, $external])
            <div class="flex gap-2">
                <dt class="shrink-0 font-medium text-[#333]">{{ $label }}:</dt>
                <dd class="min-w-0 break-all">
                    <a href="{{ $href }}" @if($external) target="_blank" rel="noopener nofollow" @endif class="hover:text-[#117a3a] hover:underline">{{ $text }}</a>
                </dd>
            </div>
            @endforeach
        </dl>
        @endif
        @if(filled($subject->story))
        <button type="button" @click="activeTab = 'story'; document.getElementById('products').scrollIntoView({ behavior: 'smooth' })"
                class="inline-flex items-center gap-0.5 text-sm font-medium text-[#117a3a] hover:underline">
            Đọc câu chuyện
            <svg class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/></svg>
        </button>
        @endif
    </div>
</div>

<div class="overflow-hidden rounded-sm bg-[#f8f9fa]">
    <div class="border-b border-[#e0e0e0] px-4 py-3 text-base font-semibold text-[#333]">Hạng sao OCOP</div>
    <div class="space-y-2 p-4">
        @foreach($starLevels as $star)
        <label class="flex cursor-pointer items-center gap-2.5 text-sm text-[#333]">
            <input type="checkbox" name="stars[]" value="{{ $star }}" form="brand-filter"
                   class="checkbox checkbox-sm checkbox-success"
                   @checked(in_array($star, $filters['stars'], true))
                   onchange="this.form.requestSubmit()">
            <span class="rating rating-xs pointer-events-none">
                @for($s = 1; $s <= 5; $s++)
                <span class="mask mask-star-2 {{ $s <= $star ? 'bg-warning' : 'bg-base-300' }}"></span>
                @endfor
            </span>
            <span class="ml-auto text-xs text-[#999]">{{ $starCounts[$star] ?? 0 }}</span>
        </label>
        @endforeach
    </div>
</div>
