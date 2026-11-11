<dialog id="restoreModal" class="modal">
    <div class="modal-box max-w-md">
        <h3 class="font-bold text-lg text-success">{{ __('Khôi phục :noun', ['noun' => $noun]) }}</h3>
        <p class="py-3 text-sm text-base-content/70">
            {{ __('Khôi phục :noun', ['noun' => $noun]) }} <strong data-restore-title class="text-base-content"></strong>?
        </p>
        <ul class="text-xs text-base-content/60 space-y-1.5 list-disc list-inside">
            <li>{{ __('Mã QR đã in giữ nguyên, không cần in lại.') }}</li>
            <li data-restore-public>{{ __(':noun đang ở trạng thái', ['noun' => ucfirst($noun)]) }} <strong>{{ __('Đã xuất bản') }}</strong> {{ __('— sau khi khôi phục sẽ hiển thị công khai ngay, mã QR trở lại trang nội dung thật.') }}</li>
            <li data-restore-private>{{ __(':noun chưa xuất bản — sau khi khôi phục cần xuất bản lại thì mã QR mới hiển thị nội dung thật.', ['noun' => ucfirst($noun)]) }}</li>
        </ul>
        <div class="modal-action mt-4">
            <button type="button" data-restore-confirm class="btn btn-success btn-sm">{{ __('Khôi phục') }}</button>
            <button type="button" class="btn btn-ghost btn-sm" onclick="restoreModal.close()">{{ __('Hủy') }}</button>
        </div>
    </div>
    <form method="dialog" class="modal-backdrop"><button>close</button></form>
</dialog>
