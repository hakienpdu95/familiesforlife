@php($sent = session('report_sent'))
@php($sent = $sent === true ? ['ref' => null, 'reason' => null, 'contact' => false] : $sent)
@php($oldReason = old('reason'))
@php($hints = $reportHints ?? [])
<footer class="tx-section mt-8 pb-6">
    @if ($sent)
        <div role="status" class="mb-3 rounded-lg bg-[#f0faf3] border border-[#cfe9d8] px-4 py-3 text-[#14532d]">
            <p class="flex items-center gap-2 text-[15px] font-semibold"><span aria-hidden="true">✓</span>{{ __('Đã gửi phản ánh tới ban quản lý') }}</p>
            @if (! empty($sent['ref']))
                <dl class="mt-2 grid grid-cols-[auto_1fr] gap-x-3 gap-y-1 text-[13px]">
                    <dt class="text-[#3f6b4f]">{{ __('Mã phản ánh') }}</dt>
                    <dd class="font-mono font-bold tracking-wide select-all">{{ $sent['ref'] }}</dd>
                    @if (! empty($sent['reason']))
                        <dt class="text-[#3f6b4f]">{{ __('Lý do') }}</dt>
                        <dd class="font-medium">{{ $sent['reason'] }}</dd>
                    @endif
                </dl>
                <p class="mt-2 text-[12.5px] leading-snug text-[#3f6b4f]">
                    {{ __(':sent Ghi lại mã trên nếu cần hỏi thêm.', ['sent' => $sent['contact'] ? __('Ban quản lý sẽ liên hệ lại theo thông tin bạn để lại.') : __('Bạn không để lại liên hệ nên sẽ không nhận được phản hồi riêng.')]) }}
                </p>
            @endif
        </div>
    @endif

    <details id="phan-anh" class="tx-details tx-card group/rp scroll-mt-20" @if ($errors->any()) open @endif>
        <summary class="tx-row-link flex items-center gap-3 px-4 py-3 min-h-[3.25rem]">
            <span class="tx-ico">@include('traceability.partials.icon', ['name' => 'flag'])</span>
            <span class="flex-1 min-w-0 leading-snug">
                <span class="block text-[15px] font-semibold">{{ __('Thông tin chưa đúng?') }}</span>
                <span class="block text-[13px] text-[#78716c]">{{ __('Báo cho ban quản lý, không cần đăng nhập') }}</span>
            </span>
            <span class="tx-chevron transition-transform group-open/rp:rotate-90">@include('traceability.partials.icon', ['name' => 'chevron'])</span>
        </summary>

        <form method="POST" action="{{ route('traceability.report', $qr_code) }}" class="px-4 pb-4 pt-3 space-y-4 border-t border-[var(--tx-line)]"
              oninput="var c=this.elements.content,n=this.querySelector('[data-count]');if(n)n.textContent=c.value.trim().length"
              onchange="var r=this.querySelector('[name=reason]:checked'),c=this.elements.content;if(r&&r.dataset.placeholder)c.placeholder=r.dataset.placeholder">
            @csrf
            <input type="text" name="website" value="" tabindex="-1" autocomplete="off" class="hidden" aria-hidden="true">

            <p class="text-[12.5px] text-[#78716c] leading-snug">{{ __('Phản ánh về') }} <span class="font-semibold text-[#44403c]">{{ $title }}</span> {{ __('· mã QR') }} <span class="font-mono">{{ $qr_code }}</span></p>

            <fieldset>
                <legend class="text-[13px] font-semibold text-[#44403c] mb-2">{{ __('1. Bạn thấy vấn đề gì?') }}</legend>
                <div class="grid gap-2">
                    @foreach ($reportReasons as $value => $label)
                        <label class="flex items-start gap-3 rounded-lg border border-[rgb(28_25_23/.12)] px-3 py-2.5 cursor-pointer transition-colors has-[:checked]:border-primary has-[:checked]:bg-[color-mix(in_oklab,var(--color-primary)_6%,transparent)]">
                            <input type="radio" name="reason" value="{{ $value }}" required @checked($oldReason === $value)
                                   data-placeholder="{{ $hints[$value][1] ?? '' }}" class="radio radio-sm mt-0.5 shrink-0 border-[#cfcac4] text-primary checked:border-primary">
                            <span class="min-w-0 leading-snug">
                                <span @class(['block text-[14px] font-semibold', 'text-[#b91c1c]' => $value === 'suspected_fake'])>{{ $label }}</span>
                                @if (! empty($hints[$value][0]))
                                    <span class="block text-[12.5px] text-[#78716c]">{{ $hints[$value][0] }}</span>
                                @endif
                            </span>
                        </label>
                    @endforeach
                </div>
                @error('reason')<span class="block mt-1 text-xs text-error">{{ $message }}</span>@enderror
            </fieldset>

            <label class="block">
                <span class="flex flex-wrap items-baseline justify-between gap-x-3 gap-y-0.5 mb-1.5">
                    <span class="text-[13px] font-semibold text-[#44403c]">{{ __('2. Mô tả cụ thể') }}</span>
                    <span class="text-[12px] text-[#a8a29e] tabular-nums shrink-0"><span data-count>{{ mb_strlen(trim((string) old('content'))) }}</span>/1000</span>
                </span>
                <textarea name="content" rows="4" minlength="10" aria-describedby="rp-min" maxlength="1000" required class="textarea tx-field w-full"
                          placeholder="{{ $hints[(string) $oldReason][1] ?? __('Nơi mua, thời điểm, điều bạn thấy chưa đúng…') }}">{{ old('content') }}</textarea>
                <span id="rp-min" class="block mt-1 text-[12px] text-[#a8a29e]">{{ __('Tối thiểu 10 ký tự.') }}</span>
                @error('content')<span class="block mt-1 text-xs text-error">{{ $message }}</span>@enderror
            </label>

            <label class="block">
                <span class="block text-[13px] font-semibold text-[#44403c] mb-1.5">{{ __('3. Liên hệ để nhận phản hồi') }} <span class="font-normal text-[#a8a29e]">{{ __('(không bắt buộc)') }}</span></span>
                <input type="text" name="contact" value="{{ old('contact') }}" maxlength="120" autocomplete="tel" class="input tx-field h-12 w-full" placeholder="{{ __('Số điện thoại hoặc email') }}">
                <span class="block mt-1 text-[12px] text-[#a8a29e]">{{ __('Chỉ ban quản lý xem được, không hiển thị trên trang này.') }}</span>
                @error('contact')<span class="block mt-1 text-xs text-error">{{ $message }}</span>@enderror
            </label>

            <button class="btn btn-neutral w-full h-12 rounded-lg shadow-none">{{ __('Gửi phản ánh') }}</button>

            <div class="rounded-lg bg-[#fafaf9] border border-[var(--tx-line)] px-3 py-2.5">
                <p class="text-[12px] font-semibold text-[#57534e]">{{ __('Sau khi gửi') }}</p>
                <ol class="mt-1.5 space-y-1 text-[12px] leading-snug text-[#78716c]">
                    <li class="flex gap-2"><span class="w-4 h-4 rounded-full bg-[#e7e5e4] text-[10px] font-bold text-[#57534e] flex items-center justify-center shrink-0">1</span>{{ __('Bạn nhận mã phản ánh để tra cứu khi cần.') }}</li>
                    <li class="flex gap-2"><span class="w-4 h-4 rounded-full bg-[#e7e5e4] text-[10px] font-bold text-[#57534e] flex items-center justify-center shrink-0">2</span>{{ __('Ban quản lý nhận thông báo ngay, kiểm tra và đối chiếu với chủ thể.') }}</li>
                    <li class="flex gap-2"><span class="w-4 h-4 rounded-full bg-[#e7e5e4] text-[10px] font-bold text-[#57534e] flex items-center justify-center shrink-0">3</span>{{ __('Nếu phản ánh đúng, thông tin trên trang được điều chỉnh; bạn được liên hệ lại nếu có để lại liên hệ.') }}</li>
                </ol>
            </div>
        </form>
    </details>
</footer>
