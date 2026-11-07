@php($banner = match ($status) {
    'archived' => ['tone' => 'bg-[#fef8e8] text-[#854d0e]', 'dot' => 'bg-[#d97706]', 'title' => 'Bản lưu trữ', 'text' => 'Nội dung đã gỡ khỏi hệ thống'.($archived_at ? ', lưu ngày '.$archived_at : '')],
    'revoked' => ['tone' => 'bg-[#fef2f2] text-[#991b1b]', 'dot' => 'bg-[#dc2626]', 'title' => 'Mã đã bị thu hồi', 'text' => ($revoked_at ? 'từ '.$revoked_at.', ' : '').'không còn giá trị xác thực'],
    default => ['tone' => 'bg-[#f0faf3] text-[#14532d]', 'dot' => null, 'title' => 'Đã xác thực', 'text' => 'thông tin do '.$brand.' công bố'],
})
<div role="{{ $status === 'valid' ? 'status' : 'alert' }}" class="flex items-center gap-2.5 px-4 py-2.5 border-b border-[var(--tx-line)] {{ $banner['tone'] }}">
    @if ($banner['dot'])
        <span class="w-2 h-2 rounded-full shrink-0 {{ $banner['dot'] }}" aria-hidden="true"></span>
    @else
        <svg viewBox="0 0 20 20" fill="currentColor" class="w-[18px] h-[18px] shrink-0 text-[#16a34a]" aria-hidden="true"><path fill-rule="evenodd" d="M10 18a8 8 0 1 0 0-16 8 8 0 0 0 0 16Zm3.86-9.81a.75.75 0 0 0-1.22-.88l-3.24 4.5-1.6-1.6a.75.75 0 1 0-1.06 1.06l2.22 2.22a.75.75 0 0 0 1.14-.09l3.76-5.21Z" clip-rule="evenodd"/></svg>
    @endif
    <p class="flex-1 min-w-0 truncate text-[13.5px]"><strong class="font-semibold">{{ $banner['title'] }}</strong> <span class="opacity-80">· {{ $banner['text'] }}</span></p>
    <span class="shrink-0 font-mono text-[12px] font-semibold tracking-wider opacity-75">{{ $qr_code }}</span>
</div>
