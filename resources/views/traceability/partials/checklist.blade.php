@php($total = count($items))
@php($done = collect($items)->filter(fn ($i) => $i['count'] > 0)->count())
@php($latest = collect($items)->pluck('updated_at')->filter()->sortBy(fn ($d) => \Illuminate\Support\Carbon::createFromFormat('d/m/Y', $d))->last())
@php($missing = collect($items)->filter(fn ($i) => $i['count'] === 0)->pluck('label'))
@php($request = 'Đề nghị bổ sung '.mb_strtolower($label).' còn thiếu: '.$missing->implode(', ').'.')
@php($ask = "var d=document.getElementById('phan-anh');d.open=true;var r=d.querySelector('[name=reason]'),c=d.querySelector('[name=content]');if(r&&!r.value)r.value='other';if(c&&!c.value)c.value=".\Illuminate\Support\Js::from($request).";")

@if ($done > 0)
    <details class="tx-details group/doc border-t border-[#ead9a8] bg-base-100">
        <summary class="tx-row-link flex items-center gap-3 px-3.5 py-2.5">
            <span class="flex-1 min-w-0 leading-snug">
                <span class="block text-[12px] text-[#78716c]">{{ $label }}</span>
                <span class="block text-[14px] font-semibold">
                    <span @class(['text-[#15803d]' => $done === $total])>{{ $done }}/{{ $total }} loại đã nộp</span>@if ($latest)<span class="font-normal text-[#78716c]"> · cập nhật {{ $latest }}</span>@endif
                </span>
            </span>
            <span class="flex w-14 gap-0.5 shrink-0" aria-hidden="true">
                @foreach ($items as $item)
                    <span @class(['h-1.5 flex-1 rounded-full', 'bg-[#16a34a]' => $item['count'] > 0, 'bg-[#e7e5e4]' => $item['count'] === 0])></span>
                @endforeach
            </span>
            <span class="tx-chevron transition-transform group-open/doc:rotate-90">@include('traceability.partials.icon', ['name' => 'chevron'])</span>
        </summary>
        <ul class="px-3.5 pb-3 space-y-2.5 text-[14px]">
            @foreach ($items as $item)
                @php($files = $item['files'] ?? [])
                <li>
                    <div class="flex items-center gap-2.5">
                        @if ($item['count'] > 0)
                            <span class="w-4 h-4 rounded-full bg-[#16a34a] text-white flex items-center justify-center shrink-0" aria-hidden="true"><svg viewBox="0 0 20 20" fill="currentColor" class="w-3 h-3"><path fill-rule="evenodd" d="M16.7 5.3a1 1 0 0 1 0 1.4l-7.5 7.5a1 1 0 0 1-1.4 0l-3.5-3.5a1 1 0 1 1 1.4-1.4l2.8 2.8 6.8-6.8a1 1 0 0 1 1.4 0Z" clip-rule="evenodd"/></svg></span>
                        @else
                            <span class="w-4 h-4 rounded-full border-[1.5px] border-[#d6d3d1] shrink-0" aria-hidden="true"></span>
                        @endif
                        <span @class(['flex-1 min-w-0', 'text-[#a8a29e]' => $item['count'] === 0])>{{ $item['label'] }}</span>
                        <span class="text-[12px] text-[#a8a29e] shrink-0">{{ $item['count'] > 0 ? ($item['updated_at'] ?: 'Đã nộp') : 'Chưa có' }}</span>
                    </div>
                    @if ($files)
                        <div class="mt-1.5 ml-[26px] flex flex-wrap gap-1.5">
                            @foreach ($files as $file)
                                <a href="{{ \App\Shared\Traceability\TraceabilityService::documentUrl($qr_code, $file['uuid']) }}" target="_blank" rel="noopener nofollow"
                                   class="inline-flex items-center gap-1.5 h-8 px-2.5 rounded-md border border-[rgb(28_25_23/.12)] text-[12.5px] font-semibold hover:border-primary hover:text-primary transition-colors">
                                    <span @class(['inline-flex items-center justify-center h-[18px] px-1 rounded-[4px] text-[10px] font-bold text-white', 'bg-[#dc2626]' => $file['type'] === 'PDF', 'bg-[#2563eb]' => $file['type'] !== 'PDF'])>{{ $file['type'] }}</span>
                                    Xem{{ count($files) > 1 ? ' tệp '.$loop->iteration : '' }}
                                    <span class="font-normal text-[#a8a29e]">{{ $file['size'] }}</span>
                                </a>
                            @endforeach
                        </div>
                    @endif
                </li>
            @endforeach
        </ul>
        @if ($missing->isNotEmpty())
            <div class="px-3.5 pb-3">
                <a href="#phan-anh" onclick="{{ $ask }}" class="text-[13px] font-semibold text-primary hover:underline">Đề nghị bổ sung hồ sơ còn thiếu</a>
            </div>
        @endif
    </details>
@else
    <div class="flex items-center gap-3 px-3.5 py-2.5 border-t border-[#ead9a8] bg-base-100">
        <span class="flex-1 min-w-0 leading-snug">
            <span class="block text-[12px] text-[#78716c]">{{ $label }}</span>
            <span class="block text-[14px] font-semibold text-[#57534e]">Chưa công bố</span>
        </span>
        <a href="#phan-anh" onclick="{{ $ask }}" class="tx-pill shrink-0">Đề nghị bổ sung</a>
    </div>
@endif
