<section id="phan-anh" class="px-4 mt-8">
    @if (session('report_sent'))
        <div role="status" class="rounded-lg border border-green-300 bg-green-50 text-green-800 text-sm px-3 py-2.5 mb-3">
            <i class="fa-solid fa-circle-check mr-1" aria-hidden="true"></i>
            Cảm ơn bạn! Phản ánh đã được gửi tới ban quản lý để kiểm tra.
        </div>
    @endif
    <details class="rounded-xl border border-base-300 group" @if ($errors->any()) open @endif>
        <summary class="flex items-center justify-between gap-3 px-3 py-3 cursor-pointer list-none text-sm font-semibold">
            <span class="flex items-center gap-2"><i class="fa-solid fa-flag text-error" aria-hidden="true"></i> Phản ánh thông tin sai / nghi ngờ tem giả</span>
            <i class="fa-solid fa-chevron-right text-xs text-base-content/40 transition-transform group-open:rotate-90" aria-hidden="true"></i>
        </summary>
        <form method="POST" action="{{ route('traceability.report', $qr_code) }}" class="px-3 pb-3 space-y-3">
            @csrf
            <input type="text" name="website" value="" tabindex="-1" autocomplete="off" class="hidden" aria-hidden="true">
            <div class="form-control">
                <label class="label py-0 pb-1"><span class="label-text text-xs font-medium">Lý do</span></label>
                <select name="reason" class="select select-bordered select-sm w-full" required>
                    <option value="">— Chọn lý do —</option>
                    @foreach ($reportReasons as $value => $label)
                        <option value="{{ $value }}" @selected(old('reason') === $value)>{{ $label }}</option>
                    @endforeach
                </select>
                @error('reason')<p class="mt-1 text-xs text-error">{{ $message }}</p>@enderror
            </div>
            <div class="form-control">
                <label class="label py-0 pb-1"><span class="label-text text-xs font-medium">Nội dung</span></label>
                <textarea name="content" rows="3" minlength="10" maxlength="1000" required class="textarea textarea-bordered textarea-sm w-full"
                          placeholder="Mô tả điều bạn thấy chưa đúng (nơi mua, thời điểm...)">{{ old('content') }}</textarea>
                @error('content')<p class="mt-1 text-xs text-error">{{ $message }}</p>@enderror
            </div>
            <div class="form-control">
                <label class="label py-0 pb-1"><span class="label-text text-xs font-medium">Liên hệ (không bắt buộc)</span></label>
                <input type="text" name="contact" value="{{ old('contact') }}" maxlength="120" class="input input-bordered input-sm w-full" placeholder="Số điện thoại hoặc email để chúng tôi phản hồi">
                @error('contact')<p class="mt-1 text-xs text-error">{{ $message }}</p>@enderror
            </div>
            <button class="btn btn-error btn-sm w-full gap-2"><i class="fa-solid fa-paper-plane" aria-hidden="true"></i> Gửi phản ánh</button>
        </form>
    </details>
</section>
