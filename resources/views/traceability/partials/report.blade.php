<footer class="px-5 mt-12 pt-6 pb-8 border-t border-base-300 bg-base-200/40">
    @if (session('report_sent'))
        <p role="status" class="mb-4 text-sm text-success font-medium">Cảm ơn bạn. Phản ánh đã được gửi tới ban quản lý.</p>
    @endif

    <details id="phan-anh" class="tx-details" @if ($errors->any()) open @endif>
        <summary class="flex items-center justify-between gap-3 text-sm">
            <span><span class="font-medium">Thông tin chưa đúng?</span> <span class="text-base-content/60">Gửi phản ánh cho ban quản lý</span></span>
            <span class="tx-plus font-mono text-lg text-base-content/40 leading-none"></span>
        </summary>
        <form method="POST" action="{{ route('traceability.report', $qr_code) }}" class="mt-4 space-y-3">
            @csrf
            <input type="text" name="website" value="" tabindex="-1" autocomplete="off" class="hidden" aria-hidden="true">
            <label class="block">
                <span class="block text-xs text-base-content/60 mb-1">Lý do</span>
                <select name="reason" class="select select-bordered select-sm w-full rounded-md bg-base-100" required>
                    <option value="">Chọn lý do</option>
                    @foreach ($reportReasons as $value => $label)
                        <option value="{{ $value }}" @selected(old('reason') === $value)>{{ $label }}</option>
                    @endforeach
                </select>
                @error('reason')<span class="block mt-1 text-xs text-error">{{ $message }}</span>@enderror
            </label>
            <label class="block">
                <span class="block text-xs text-base-content/60 mb-1">Nội dung</span>
                <textarea name="content" rows="3" minlength="10" maxlength="1000" required class="textarea textarea-bordered textarea-sm w-full rounded-md bg-base-100"
                          placeholder="Nơi mua, thời điểm, điều bạn thấy chưa đúng…">{{ old('content') }}</textarea>
                @error('content')<span class="block mt-1 text-xs text-error">{{ $message }}</span>@enderror
            </label>
            <label class="block">
                <span class="block text-xs text-base-content/60 mb-1">Liên hệ (không bắt buộc)</span>
                <input type="text" name="contact" value="{{ old('contact') }}" maxlength="120" class="input input-bordered input-sm w-full rounded-md bg-base-100" placeholder="Số điện thoại hoặc email">
                @error('contact')<span class="block mt-1 text-xs text-error">{{ $message }}</span>@enderror
            </label>
            <button class="btn btn-neutral btn-sm rounded-md">Gửi phản ánh</button>
        </form>
    </details>
</footer>
