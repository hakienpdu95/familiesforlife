<footer class="tx-section mt-8 pb-6">
    @if (session('report_sent'))
        <p role="status" class="mb-3 flex items-center gap-2 rounded-lg bg-[#f0faf3] border border-[#cfe9d8] px-4 py-3 text-[14px] text-[#14532d] font-medium">✓ Cảm ơn bạn. Phản ánh đã được gửi tới ban quản lý.</p>
    @endif

    <details id="phan-anh" class="tx-details tx-card group/rp scroll-mt-20" @if ($errors->any()) open @endif>
        <summary class="tx-row-link flex items-center gap-3 px-4 py-3 min-h-[3.25rem]">
            <span class="tx-ico">@include('traceability.partials.icon', ['name' => 'flag'])</span>
            <span class="flex-1 min-w-0 leading-snug">
                <span class="block text-[15px] font-semibold">Thông tin chưa đúng?</span>
                <span class="block text-[13px] text-[#78716c]">Gửi phản ánh tới ban quản lý, không cần đăng nhập</span>
            </span>
            <span class="tx-chevron transition-transform group-open/rp:rotate-90">@include('traceability.partials.icon', ['name' => 'chevron'])</span>
        </summary>
        <form method="POST" action="{{ route('traceability.report', $qr_code) }}" class="px-4 pb-4 pt-4 space-y-3 border-t border-[var(--tx-line)]">
            @csrf
            <input type="text" name="website" value="" tabindex="-1" autocomplete="off" class="hidden" aria-hidden="true">
            <label class="block">
                <span class="block text-[13px] font-medium text-[#57534e] mb-1.5">Lý do</span>
                <select name="reason" class="select tx-field h-12" required>
                    <option value="">Chọn lý do</option>
                    @foreach ($reportReasons as $value => $label)
                        <option value="{{ $value }}" @selected(old('reason') === $value)>{{ $label }}</option>
                    @endforeach
                </select>
                @error('reason')<span class="block mt-1 text-xs text-error">{{ $message }}</span>@enderror
            </label>
            <label class="block">
                <span class="block text-[13px] font-medium text-[#57534e] mb-1.5">Nội dung</span>
                <textarea name="content" rows="3" minlength="10" maxlength="1000" required class="textarea tx-field"
                          placeholder="Nơi mua, thời điểm, điều bạn thấy chưa đúng…">{{ old('content') }}</textarea>
                @error('content')<span class="block mt-1 text-xs text-error">{{ $message }}</span>@enderror
            </label>
            <label class="block">
                <span class="block text-[13px] font-medium text-[#57534e] mb-1.5">Liên hệ (không bắt buộc)</span>
                <input type="text" name="contact" value="{{ old('contact') }}" maxlength="120" class="input tx-field h-12" placeholder="Số điện thoại hoặc email">
                @error('contact')<span class="block mt-1 text-xs text-error">{{ $message }}</span>@enderror
            </label>
            <button class="btn btn-neutral w-full h-12 rounded-lg shadow-none">Gửi phản ánh</button>
        </form>
    </details>
    <p class="tx-note text-center">Mã {{ $qr_code }} · Hệ thống truy xuất {{ $brand }}</p>
</footer>
