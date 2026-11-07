@if ($status === 'archived')
    <p role="alert" class="px-5 py-2.5 text-[13px] bg-[#fdf6e3] text-[#713f12] border-b border-[#f1e2b3]">
        <strong class="font-semibold">Bản lưu trữ.</strong> Nội dung đã được gỡ khỏi hệ thống{{ $archived_at ? ', thông tin lưu ngày '.$archived_at : '' }}.
    </p>
@elseif ($status === 'revoked')
    <p role="alert" class="px-5 py-2.5 text-[13px] bg-[#fdecec] text-[#7f1d1d] border-b border-[#f5c9c9]">
        <strong class="font-semibold">Mã {{ $qr_code }} đã bị thu hồi</strong>{{ $revoked_at ? ' từ '.$revoked_at : '' }} và không còn giá trị xác thực.
    </p>
@endif
