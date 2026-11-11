<div role="tablist" class="tabs tabs-boxed inline-flex mb-4">
    <button type="button" role="tab" class="tab" :class="!filters.trashed && 'tab-active'" @click="setTrashed(false)">{{ $activeLabel }}</button>
    <button type="button" role="tab" class="tab gap-1.5" :class="filters.trashed && 'tab-active'" @click="setTrashed(true)">
        {{ __('Thùng rác') }}
        <span class="badge badge-sm" :class="trashedCount > 0 ? 'badge-warning' : 'badge-ghost'" x-text="trashedCount">0</span>
    </button>
</div>

<div x-show="filters.trashed" x-cloak class="alert alert-warning text-sm mb-4">
    <svg class="w-5 h-5 shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01M5.07 19h13.86a2 2 0 001.73-3L13.73 4a2 2 0 00-3.46 0L3.34 16a2 2 0 001.73 3z"/></svg>
    <span>{{ __(':noun trong thùng rác vẫn giữ mã QR — người quét sẽ thấy trang', ['noun' => ucfirst($noun)]) }} <strong>{{ __('bản lưu trữ') }}</strong>{{ __('. Khôi phục :noun để mã QR hiển thị lại nội dung thật.', ['noun' => $noun]) }}</span>
</div>
