@php($items = collect($items))
@php($done = $items->filter(fn ($i) => $i['count'] > 0)->count())
@php($missing = $items->filter(fn ($i) => $i['count'] === 0)->pluck('label'))
@php($requiredMissing = $items->filter(fn ($i) => $i['count'] === 0 && ! empty($i['required']))->pluck('label'))
@php($hasFiles = $items->contains(fn ($i) => ! empty($i['files'])))
@php($request = 'Đề nghị bổ sung '.mb_strtolower($label).' còn thiếu: '.$missing->implode(', ').'.')
@php($prefill = fn (string $reason, string $text) => "var d=document.getElementById('phan-anh');d.open=true;var r=d.querySelector('[name=reason][value='+".\Illuminate\Support\Js::from($reason)."+']'),c=d.querySelector('[name=content]');if(r&&!d.querySelector('[name=reason]:checked')){r.checked=true;r.form.dispatchEvent(new Event('change'))}if(c&&!c.value){c.value=".\Illuminate\Support\Js::from($text).";c.form.dispatchEvent(new Event('input'))}")
@php($ask = $prefill('other', $request))
@php($stale = $items->filter(fn ($i) => ! empty($i['stale']))->pluck('label'))

@if ($done > 0)
    <details class="tx-details group/doc border-t border-[#ead9a8] bg-base-100">
        <summary class="tx-row-link flex items-center gap-3 px-3.5 py-2.5 min-h-11">
            <span class="flex-1 min-w-0 leading-snug">
                <span class="flex items-center gap-2">
                    <span class="text-[14px] font-semibold">{{ $label }}</span>
                    <span class="flex w-12 gap-0.5" aria-hidden="true">
                        @foreach ($items as $item)
                            <span @class(['h-1 flex-1 rounded-full', 'bg-[#16a34a]' => $item['count'] > 0, 'bg-[#e7e5e4]' => $item['count'] === 0])></span>
                        @endforeach
                    </span>
                </span>
                @if ($requiredMissing->isNotEmpty())
                    <span class="block text-[12.5px] font-medium text-[#b45309] line-clamp-2">Thiếu hồ sơ bắt buộc: {{ $requiredMissing->implode(', ') }}</span>
                @elseif ($stale->isNotEmpty())
                    <span class="block text-[12.5px] font-medium text-[#b45309] line-clamp-2">Cần cập nhật: {{ $stale->implode(', ') }}</span>
                @elseif ($missing->isNotEmpty())
                    <span class="block text-[12.5px] text-[#78716c] line-clamp-2">Chưa công bố: {{ $missing->implode(', ') }}</span>
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
                <li class="flex gap-2.5 px-3.5 py-2.5">
                    @if ($has)
                        <span class="mt-0.5 w-4 h-4 rounded-full bg-[#16a34a] text-white flex items-center justify-center shrink-0" aria-hidden="true"><svg viewBox="0 0 20 20" fill="currentColor" class="w-3 h-3"><path fill-rule="evenodd" d="M16.7 5.3a1 1 0 0 1 0 1.4l-7.5 7.5a1 1 0 0 1-1.4 0l-3.5-3.5a1 1 0 1 1 1.4-1.4l2.8 2.8 6.8-6.8a1 1 0 0 1 1.4 0Z" clip-rule="evenodd"/></svg></span>
                    @elseif (! empty($item['required']))
                        <span class="mt-0.5 w-4 h-4 rounded-full bg-[#f59e0b] text-white text-[11px] font-bold leading-none flex items-center justify-center shrink-0" aria-hidden="true">!</span>
                    @else
                        <span class="mt-0.5 w-4 h-4 rounded-full border-[1.5px] border-[#d6d3d1] shrink-0" aria-hidden="true"></span>
                    @endif
                    <div class="flex-1 min-w-0">
                        <div class="flex items-baseline justify-between gap-3">
                            <span @class(['text-[13.5px] font-semibold leading-snug', 'text-[#78716c]' => ! $has])>{{ $item['label'] }}</span>
                            <span @class(['text-[12px] shrink-0', 'text-[#78716c]' => $has, 'text-[#a8a29e]' => ! $has && empty($item['required']), 'text-[#b45309] font-semibold' => ! $has && ! empty($item['required'])])>
                                @unless ($has)
                                    Chưa công bố
                                @endunless
                            </span>
                        </div>
                        @if (! empty($item['hint']))
                            <p class="mt-0.5 text-[12px] leading-snug text-[#a8a29e]">{{ $item['hint'] }}</p>
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
                                           data-doc data-doc-type="{{ $file['type'] }}" data-doc-title="{{ $item['label'] }}{{ count($files) > 1 ? ' · tệp '.$loop->iteration : '' }}"
                                           data-doc-meta="{{ implode(' · ', array_filter([$file['type'], $file['size'], ! empty($file['uploaded_at']) ? 'đăng '.$file['uploaded_at'] : null, ! empty($file['ref']) ? 'mã '.$file['ref'] : null])) }}"
                                           aria-label="Xem {{ $item['label'] }}{{ count($files) > 1 ? ', tệp '.$loop->iteration.' trên '.count($files) : '' }} ({{ $file['type'] }}, {{ $file['size'] }}, mã {{ $file['ref'] ?? '' }})"
                                           class="group/file flex flex-1 min-w-0 items-center gap-2 pl-2.5 py-1.5 min-h-10 hover:text-primary transition-colors">
                                            <span @class(['inline-flex items-center justify-center h-[18px] px-1 rounded-[4px] text-[10px] font-bold text-white shrink-0', 'bg-[#dc2626]' => $file['type'] === 'PDF', 'bg-[#2563eb]' => $file['type'] !== 'PDF'])>{{ $file['type'] }}</span>
                                            <span class="min-w-0 leading-tight">
                                                <span class="block text-[12.5px] font-semibold leading-tight group-hover/file:underline">Xem{{ count($files) > 1 ? ' tệp '.$loop->iteration : '' }}@if ($loop->first && count($files) > 1)<span class="ml-1 font-normal text-[#15803d]">mới nhất</span>@endif</span>
                                                <span class="flex flex-wrap gap-x-1 text-[11.5px] leading-snug text-[#a8a29e]">@foreach (array_values(array_filter([$file['size'], ! empty($file['uploaded_at']) ? 'đăng '.$file['uploaded_at'] : null, ! empty($file['ref']) ? 'mã '.$file['ref'] : null])) as $part)<span class="whitespace-nowrap">{{ $loop->first ? '' : '· ' }}{{ $part }}</span>@endforeach</span>
                                            </span>
                                        </a>
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
        <div class="flex items-start gap-3 px-3.5 py-2 border-t border-[var(--tx-line)] bg-[#fafaf9]">
            <p class="flex-1 text-[12px] leading-snug text-[#78716c]">Hồ sơ do chủ thể cung cấp.</p>
            @if ($missing->isNotEmpty())
                <a href="#phan-anh" onclick="{{ $ask }}" class="shrink-0 text-[12.5px] font-semibold text-primary hover:underline">Đề nghị bổ sung</a>
            @endif
        </div>
    </details>
@else
    <div class="flex items-center gap-3 px-3.5 py-2.5 border-t border-[#ead9a8] bg-base-100">
        <span class="flex-1 min-w-0 leading-snug">
            <span class="block text-[12px] text-[#78716c]">{{ $label }}</span>
            <span class="block text-[13.5px] font-semibold text-[#57534e]">Chưa công bố hồ sơ nào</span>
            @if ($requiredMissing->isNotEmpty())
                <span class="block text-[12.5px] font-medium text-[#b45309] line-clamp-2">Thiếu hồ sơ bắt buộc: {{ $requiredMissing->implode(', ') }}</span>
            @endif
        </span>
        <a href="#phan-anh" onclick="{{ $ask }}" class="tx-pill shrink-0">Đề nghị bổ sung</a>
    </div>
@endif

@if ($hasFiles)
    @once
        <dialog id="tx-doc-viewer" aria-label="Xem hồ sơ" class="m-0 w-full h-dvh max-w-none max-h-none p-0 bg-[#0c0a09] text-white backdrop:bg-black/80 open:flex flex-col overscroll-contain">
            <div class="flex items-start gap-3 pl-[max(1rem,env(safe-area-inset-left))] pr-[max(1rem,env(safe-area-inset-right))] pt-[max(.75rem,env(safe-area-inset-top))] pb-3 border-b border-white/10">
                <div class="flex-1 min-w-0">
                    <p data-v-title class="text-[15px] font-semibold leading-snug truncate"></p>
                    <p class="mt-0.5 text-[12px] text-white/55 truncate"><span data-v-count class="tabular-nums"></span><span data-v-meta></span></p>
                </div>
                <button type="button" data-v-close class="shrink-0 w-10 h-10 -mr-2 rounded-full flex items-center justify-center text-white/80 hover:bg-white/10 hover:text-white" aria-label="Đóng">
                    <svg viewBox="0 0 20 20" fill="currentColor" class="w-5 h-5" aria-hidden="true"><path d="M5.3 5.3a1 1 0 0 1 1.4 0L10 8.6l3.3-3.3a1 1 0 1 1 1.4 1.4L11.4 10l3.3 3.3a1 1 0 0 1-1.4 1.4L10 11.4l-3.3 3.3a1 1 0 0 1-1.4-1.4L8.6 10 5.3 6.7a1 1 0 0 1 0-1.4Z"/></svg>
                </button>
            </div>
            <div data-v-stage class="relative flex-1 min-h-0 flex items-center justify-center select-none touch-pan-y">
                <img data-v-img alt="" class="hidden max-w-full max-h-full object-contain" decoding="async">
                <iframe data-v-frame title="Tệp PDF" class="hidden w-full h-full bg-white"></iframe>
                <div data-v-pdf class="hidden flex-col items-center gap-3 px-6 text-center">
                    <span class="inline-flex items-center justify-center w-14 h-16 rounded-md bg-[#dc2626] text-[13px] font-bold">PDF</span>
                    <p class="text-[14px] text-white/75">Trình duyệt trên thiết bị này không hiển thị PDF trong trang.</p>
                    <a data-v-open href="#" target="_blank" rel="noopener nofollow" class="inline-flex items-center h-10 px-4 rounded-lg bg-white text-[#1c1917] text-[14px] font-semibold">Mở tệp PDF</a>
                </div>
                <p data-v-error class="hidden px-6 text-center text-[14px] text-white/75">Không tải được tệp. Liên kết xem có hạn 30 phút — vui lòng tải lại trang.</p>
                <span data-v-loading class="hidden absolute inset-0 m-auto w-8 h-8 rounded-full border-2 border-white/25 border-t-white animate-spin"></span>
                <button type="button" data-v-step="-1" class="absolute left-2 top-1/2 -translate-y-1/2 w-11 h-11 rounded-full bg-black/45 backdrop-blur flex items-center justify-center hover:bg-black/70 disabled:opacity-0" aria-label="Tệp trước">
                    <svg viewBox="0 0 20 20" fill="currentColor" class="w-5 h-5 rotate-180" aria-hidden="true"><path fill-rule="evenodd" d="M7.2 4.2a1 1 0 0 1 1.4 0l5.1 5.1a1 1 0 0 1 0 1.4l-5.1 5.1a1 1 0 1 1-1.4-1.4L11.6 10 7.2 5.6a1 1 0 0 1 0-1.4Z" clip-rule="evenodd"/></svg>
                </button>
                <button type="button" data-v-step="1" class="absolute right-2 top-1/2 -translate-y-1/2 w-11 h-11 rounded-full bg-black/45 backdrop-blur flex items-center justify-center hover:bg-black/70 disabled:opacity-0" aria-label="Tệp sau">
                    <svg viewBox="0 0 20 20" fill="currentColor" class="w-5 h-5" aria-hidden="true"><path fill-rule="evenodd" d="M7.2 4.2a1 1 0 0 1 1.4 0l5.1 5.1a1 1 0 0 1 0 1.4l-5.1 5.1a1 1 0 1 1-1.4-1.4L11.6 10 7.2 5.6a1 1 0 0 1 0-1.4Z" clip-rule="evenodd"/></svg>
                </button>
            </div>
            <div data-v-strip class="flex gap-2 px-4 py-3 pb-[max(.75rem,env(safe-area-inset-bottom))] border-t border-white/10 overflow-x-auto [scrollbar-width:none] [&::-webkit-scrollbar]:hidden"></div>
        </dialog>
        <script>
            (function () {
                var dlg = document.getElementById('tx-doc-viewer');
                if (!dlg || typeof dlg.showModal !== 'function') return;
                var links = Array.prototype.slice.call(document.querySelectorAll('a[data-doc]'));
                var $ = function (s) { return dlg.querySelector(s); };
                var img = $('[data-v-img]'), frame = $('[data-v-frame]'), pdf = $('[data-v-pdf]'), err = $('[data-v-error]'), load = $('[data-v-loading]'), strip = $('[data-v-strip]');
                var prev = $('[data-v-step="-1"]'), next = $('[data-v-step="1"]');
                var inlinePdf = window.matchMedia('(min-width: 768px) and (pointer: fine)').matches;
                var cur = -1;

                links.forEach(function (a, i) {
                    var b = document.createElement('button');
                    b.type = 'button';
                    b.setAttribute('aria-label', a.dataset.docTitle);
                    b.className = 'shrink-0 w-14 h-14 rounded-md overflow-hidden border-2 border-transparent bg-white/10 opacity-55 transition data-[active]:border-white data-[active]:opacity-100 flex items-center justify-center text-[11px] font-bold';
                    if (a.dataset.docType === 'PDF') { b.textContent = 'PDF'; b.classList.add('bg-[#dc2626]'); }
                    else { var t = new Image(); t.alt = ''; t.loading = 'lazy'; t.width = 56; t.height = 56; t.className = 'w-full h-full object-cover'; t.dataset.src = a.href; b.appendChild(t); }
                    b.addEventListener('click', function () { show(i); });
                    strip.appendChild(b);
                    a.addEventListener('click', function (e) {
                        if (e.metaKey || e.ctrlKey || e.shiftKey || e.button === 1) return;
                        e.preventDefault();
                        open(i);
                    });
                });
                if (links.length < 2) { strip.classList.add('hidden'); prev.classList.add('hidden'); next.classList.add('hidden'); }

                function hideAll() { [img, frame, pdf, err].forEach(function (el) { el.classList.add('hidden'); el.classList.remove('flex'); }); }
                function show(i) {
                    if (i < 0 || i >= links.length) return;
                    cur = i;
                    var a = links[i];
                    $('[data-v-title]').textContent = a.dataset.docTitle;
                    $('[data-v-count]').textContent = links.length > 1 ? (i + 1) + '/' + links.length + ' · ' : '';
                    $('[data-v-meta]').textContent = a.dataset.docMeta;
                    prev.disabled = i === 0;
                    next.disabled = i === links.length - 1;
                    hideAll();
                    load.classList.add('hidden');
                    if (a.dataset.docType === 'PDF') {
                        if (inlinePdf) { frame.src = a.href; frame.classList.remove('hidden'); }
                        else { frame.removeAttribute('src'); $('[data-v-open]').href = a.href; pdf.classList.remove('hidden'); pdf.classList.add('flex'); }
                    } else {
                        frame.removeAttribute('src');
                        load.classList.remove('hidden');
                        img.onload = function () { load.classList.add('hidden'); img.classList.remove('hidden'); };
                        img.onerror = function () { load.classList.add('hidden'); err.classList.remove('hidden'); };
                        img.alt = a.dataset.docTitle;
                        img.src = a.href;
                        [i - 1, i + 1].forEach(function (k) { var n = links[k]; if (n && n.dataset.docType !== 'PDF') { (new Image()).src = n.href; } });
                    }
                    Array.prototype.forEach.call(strip.children, function (b, k) {
                        b.toggleAttribute('data-active', k === i);
                        var t = b.querySelector('img[data-src]');
                        if (t && Math.abs(k - i) <= 4) { t.src = t.dataset.src; t.removeAttribute('data-src'); }
                    });
                    var on = strip.children[i];
                    if (on) strip.scrollLeft = on.offsetLeft - (strip.clientWidth - on.offsetWidth) / 2;
                }
                function open(i) { dlg.showModal(); document.documentElement.style.overflow = 'hidden'; show(i); }

                prev.addEventListener('click', function () { show(cur - 1); });
                next.addEventListener('click', function () { show(cur + 1); });
                $('[data-v-close]').addEventListener('click', function () { dlg.close(); });
                dlg.addEventListener('close', function () { document.documentElement.style.overflow = ''; frame.removeAttribute('src'); img.removeAttribute('src'); });
                dlg.addEventListener('keydown', function (e) {
                    if (e.key === 'ArrowLeft') { e.preventDefault(); show(cur - 1); }
                    if (e.key === 'ArrowRight') { e.preventDefault(); show(cur + 1); }
                });
                var x0 = null, y0 = null;
                var stage = $('[data-v-stage]');
                stage.addEventListener('touchstart', function (e) { x0 = e.touches[0].clientX; y0 = e.touches[0].clientY; }, { passive: true });
                stage.addEventListener('touchend', function (e) {
                    if (x0 === null) return;
                    var dx = e.changedTouches[0].clientX - x0, dy = e.changedTouches[0].clientY - y0;
                    if (Math.abs(dx) > 50 && Math.abs(dx) > Math.abs(dy)) show(cur + (dx < 0 ? 1 : -1));
                    x0 = null;
                });
                stage.addEventListener('click', function (e) { if (e.target === stage) dlg.close(); });
            })();
        </script>
    @endonce
@endif
