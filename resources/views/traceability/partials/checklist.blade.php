@php($items = collect($items))
@php($total = $items->count())
@php($done = $items->filter(fn ($i) => $i['count'] > 0)->count())
@php($latest = $items->pluck('updated_at')->filter()->sortBy(fn ($d) => \Illuminate\Support\Carbon::createFromFormat('d/m/Y', $d))->last())
@php($missing = $items->filter(fn ($i) => $i['count'] === 0)->pluck('label'))
@php($requiredMissing = $items->filter(fn ($i) => $i['count'] === 0 && ! empty($i['required']))->pluck('label'))
@php($hasFiles = $items->contains(fn ($i) => ! empty($i['files'])))
@php($request = 'Đề nghị bổ sung '.mb_strtolower($label).' còn thiếu: '.$missing->implode(', ').'.')
@php($prefill = fn (string $reason, string $text) => "var d=document.getElementById('phan-anh');d.open=true;var r=d.querySelector('[name=reason][value='+".\Illuminate\Support\Js::from($reason)."+']'),c=d.querySelector('[name=content]');if(r&&!d.querySelector('[name=reason]:checked')){r.checked=true;r.form.dispatchEvent(new Event('change'))}if(c&&!c.value){c.value=".\Illuminate\Support\Js::from($text).";c.form.dispatchEvent(new Event('input'))}")
@php($ask = $prefill('other', $request))
@php($stale = $items->filter(fn ($i) => ! empty($i['stale']))->pluck('label'))

@if ($done > 0)
    <details class="tx-details group/doc border-t border-[#ead9a8] bg-base-100">
        <summary class="tx-row-link flex items-center gap-3 px-3.5 py-2.5">
            <span class="flex-1 min-w-0 leading-snug">
                <span class="flex items-center gap-2">
                    <span class="text-[12px] text-[#78716c]">{{ $label }}</span>
                    <span class="flex w-12 gap-0.5" aria-hidden="true">
                        @foreach ($items as $item)
                            <span @class(['h-1 flex-1 rounded-full', 'bg-[#16a34a]' => $item['count'] > 0, 'bg-[#e7e5e4]' => $item['count'] === 0])></span>
                        @endforeach
                    </span>
                </span>
                <span class="block text-[14px] font-semibold">
                    <span @class(['text-[#15803d]' => $done === $total])>Đã công bố {{ $done }}/{{ $total }} loại</span>@if ($latest)<span class="font-normal text-[#78716c]"> · đăng gần nhất {{ $latest }}</span>@endif
                </span>
                @if ($requiredMissing->isNotEmpty())
                    <span class="block text-[12.5px] font-medium text-[#b45309] truncate">Thiếu hồ sơ bắt buộc: {{ $requiredMissing->implode(', ') }}</span>
                @elseif ($stale->isNotEmpty())
                    <span class="block text-[12.5px] font-medium text-[#b45309] truncate">Cần cập nhật: {{ $stale->implode(', ') }}</span>
                @elseif ($missing->isNotEmpty())
                    <span class="block text-[12.5px] text-[#78716c] truncate">Chưa công bố: {{ $missing->implode(', ') }}</span>
                @endif
            </span>
            <span class="shrink-0 flex items-center gap-1 text-[12.5px] font-semibold text-primary">
                <span class="group-open/doc:hidden">Xem</span><span class="hidden group-open/doc:inline">Thu gọn</span>
                <span class="tx-chevron text-current transition-transform group-open/doc:rotate-90">@include('traceability.partials.icon', ['name' => 'chevron'])</span>
            </span>
        </summary>
        <ul class="divide-y divide-[var(--tx-line)] border-t border-[var(--tx-line)]">
            @foreach ($items as $item)
                @php($files = $item['files'] ?? [])
                @php($has = $item['count'] > 0)
                <li class="flex gap-2.5 px-3.5 py-3">
                    @if ($has)
                        <span class="mt-0.5 w-4 h-4 rounded-full bg-[#16a34a] text-white flex items-center justify-center shrink-0" aria-hidden="true"><svg viewBox="0 0 20 20" fill="currentColor" class="w-3 h-3"><path fill-rule="evenodd" d="M16.7 5.3a1 1 0 0 1 0 1.4l-7.5 7.5a1 1 0 0 1-1.4 0l-3.5-3.5a1 1 0 1 1 1.4-1.4l2.8 2.8 6.8-6.8a1 1 0 0 1 1.4 0Z" clip-rule="evenodd"/></svg></span>
                    @elseif (! empty($item['required']))
                        <span class="mt-0.5 w-4 h-4 rounded-full bg-[#f59e0b] text-white text-[11px] font-bold leading-none flex items-center justify-center shrink-0" aria-hidden="true">!</span>
                    @else
                        <span class="mt-0.5 w-4 h-4 rounded-full border-[1.5px] border-[#d6d3d1] shrink-0" aria-hidden="true"></span>
                    @endif
                    <div class="flex-1 min-w-0">
                        <div class="flex items-baseline justify-between gap-3">
                            <span @class(['text-[14px] font-semibold leading-snug', 'text-[#78716c]' => ! $has])>{{ $item['label'] }}</span>
                            <span @class(['text-[12px] shrink-0', 'text-[#78716c]' => $has, 'text-[#a8a29e]' => ! $has && empty($item['required']), 'text-[#b45309] font-semibold' => ! $has && ! empty($item['required'])])>
                                @if ($has)
                                    {{ $item['updated_at'] ? 'Đăng '.(! empty($item['ago']) ? $item['ago'] : $item['updated_at']) : 'Đã công bố' }}
                                @else
                                    Chưa công bố
                                @endif
                            </span>
                        </div>
                        @if (! empty($item['hint']))
                            <p class="mt-0.5 text-[12.5px] leading-snug text-[#a8a29e]">{{ $item['hint'] }}</p>
                        @endif
                        @if (! empty($item['required']))
                            <span @class(['mt-1 inline-flex h-5 px-1.5 items-center rounded text-[11px] font-semibold', 'bg-[#e8f6ed] text-[#15803d]' => $has, 'bg-[#fef3c7] text-[#b45309]' => ! $has])>{{ $item['required'] }}</span>
                        @endif
                        @if (! empty($item['stale']))
                            <p class="mt-1.5 flex gap-1.5 text-[12px] leading-snug font-medium text-[#b45309]"><span aria-hidden="true">⚠</span>{{ $item['stale'] }}</p>
                        @endif
                        @if ($files)
                            <ul class="mt-2 rounded-md border border-[rgb(28_25_23/.1)] divide-y divide-[rgb(28_25_23/.08)]">
                                @foreach ($files as $file)
                                    <li class="flex items-center gap-2">
                                        <a href="{{ \App\Shared\Traceability\TraceabilityService::documentUrl($qr_code, $file['uuid']) }}" target="_blank" rel="noopener nofollow"
                                           aria-label="Xem {{ $item['label'] }}{{ count($files) > 1 ? ', tệp '.$loop->iteration.' trên '.count($files) : '' }} ({{ $file['type'] }}, {{ $file['size'] }}, mã {{ $file['ref'] ?? '' }})"
                                           class="group/file flex flex-1 min-w-0 items-center gap-2 pl-2 py-1.5 hover:text-primary transition-colors">
                                            <span @class(['inline-flex items-center justify-center h-[18px] px-1 rounded-[4px] text-[10px] font-bold text-white shrink-0', 'bg-[#dc2626]' => $file['type'] === 'PDF', 'bg-[#2563eb]' => $file['type'] !== 'PDF'])>{{ $file['type'] }}</span>
                                            <span class="min-w-0 leading-tight">
                                                <span class="block text-[12.5px] font-semibold group-hover/file:underline">Xem{{ count($files) > 1 ? ' tệp '.$loop->iteration : '' }}@if ($loop->first && count($files) > 1)<span class="ml-1 font-normal text-[#15803d]">mới nhất</span>@endif</span>
                                                <span class="block text-[11.5px] text-[#a8a29e] truncate">{{ implode(' · ', array_filter([$file['size'], ! empty($file['uploaded_at']) ? 'đăng '.$file['uploaded_at'] : null, ! empty($file['ref']) ? 'mã '.$file['ref'] : null])) }}</span>
                                            </span>
                                        </a>
                                        @if (! empty($file['ref']))
                                            <a href="#phan-anh" onclick="{{ $prefill('wrong_info', 'Hồ sơ "'.$item['label'].'" (mã '.$file['ref'].') có dấu hiệu chưa đúng: ') }}"
                                               class="shrink-0 px-2 py-1.5 text-[11.5px] text-[#a8a29e] hover:text-[#b91c1c] hover:underline">Báo sai</a>
                                        @endif
                                    </li>
                                @endforeach
                            </ul>
                        @elseif ($has)
                            <p class="mt-1 text-[12px] text-[#a8a29e]">Đã cập nhật trên hệ thống, tệp không mở xem công khai.</p>
                        @endif
                    </div>
                </li>
            @endforeach
        </ul>
        <div class="flex items-start gap-3 px-3.5 py-2.5 border-t border-[var(--tx-line)] bg-[#fafaf9]">
            <p class="flex-1 text-[11.5px] leading-snug text-[#78716c]">Hồ sơ do chủ thể cung cấp. Ngày hiển thị là ngày đăng tải, không phải ngày cấp{{ $hasFiles ? ' — đối chiếu ngày cấp, hiệu lực và tên sản phẩm ghi trong từng tệp. Khi phản ánh, ghi kèm mã tệp' : '' }}.</p>
            @if ($missing->isNotEmpty())
                <a href="#phan-anh" onclick="{{ $ask }}" class="shrink-0 text-[12.5px] font-semibold text-primary hover:underline">Đề nghị bổ sung</a>
            @endif
        </div>
    </details>
@else
    <div class="flex items-center gap-3 px-3.5 py-2.5 border-t border-[#ead9a8] bg-base-100">
        <span class="flex-1 min-w-0 leading-snug">
            <span class="block text-[12px] text-[#78716c]">{{ $label }}</span>
            <span class="block text-[14px] font-semibold text-[#57534e]">Chưa công bố hồ sơ nào</span>
            @if ($requiredMissing->isNotEmpty())
                <span class="block text-[12.5px] font-medium text-[#b45309] truncate">Thiếu hồ sơ bắt buộc: {{ $requiredMissing->implode(', ') }}</span>
            @endif
        </span>
        <a href="#phan-anh" onclick="{{ $ask }}" class="tx-pill shrink-0">Đề nghị bổ sung</a>
    </div>
@endif
