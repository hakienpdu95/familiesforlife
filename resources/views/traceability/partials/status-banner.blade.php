@php($banner = match ($status) {
    'archived' => [
        'tone' => 'bg-[#fef8e8] text-[#713f12]', 'ring' => 'border-[#f3e2b3]', 'accent' => 'text-[#b45309]',
        'title' => 'Bản lưu trữ', 'lead' => 'Bản lưu'.($archived_at ? ' ngày '.$archived_at : '').' · nội dung đã gỡ',
        'note' => 'Mã vẫn chính hãng nhưng thông tin không còn được cập nhật. Đối chiếu kỹ trước khi mua hoặc sử dụng.',
    ],
    'revoked' => [
        'tone' => 'bg-[#fef2f2] text-[#7f1d1d]', 'ring' => 'border-[#fbd5d5]', 'accent' => 'text-[#dc2626]',
        'title' => 'Mã đã bị thu hồi', 'lead' => ($revoked_at ? 'Từ '.$revoked_at.' · ' : '').'Không còn giá trị',
        'note' => 'Không dùng mã này để chứng minh nguồn gốc. Nếu bạn vừa mua sản phẩm mang mã này, hãy phản ánh để được kiểm tra.',
    ],
    default => [
        'tone' => 'bg-[#f0faf3] text-[#14532d]', 'ring' => 'border-[#cdebd7]', 'accent' => 'text-[#16a34a]',
        'title' => 'Đã xác thực', 'lead' => implode(' · ', array_filter([$kind ?? null, $brand])),
        'note' => 'Mã gắn cố định với đối tượng này, không thể sửa hay chuyển sang đối tượng khác.',
    ],
})
@php($rows = array_filter([
    'Đơn vị công bố' => $brand,
    'Ngày cấp mã' => $issued_at ?? null,
    $status === 'archived' ? 'Ngày lưu trữ' : 'Dữ liệu cập nhật' => $data_updated_at ?? null,
    'Ngày thu hồi' => $status === 'revoked' ? $revoked_at : null,
]))
<div role="{{ $status === 'valid' ? 'status' : 'alert' }}">
<details @if ($status === 'revoked') open @endif class="group/vf border-b {{ $banner['ring'] }} {{ $banner['tone'] }}">
    <summary class="flex items-center gap-2.5 px-4 min-h-12 py-2 cursor-pointer select-none list-none [&::-webkit-details-marker]:hidden" aria-label="{{ $banner['title'] }} — xem chi tiết xác thực mã {{ $qr_code }}">
        @if ($status === 'valid')
            <svg viewBox="0 0 24 24" fill="currentColor" class="w-5 h-5 shrink-0 {{ $banner['accent'] }}" aria-hidden="true"><path fill-rule="evenodd" d="M12.516 2.17a.75.75 0 0 0-1.032 0 11.209 11.209 0 0 1-7.877 3.08.75.75 0 0 0-.722.515A12.74 12.74 0 0 0 2.25 9.75c0 5.942 4.064 10.933 9.563 12.348a.749.749 0 0 0 .374 0c5.499-1.415 9.563-6.406 9.563-12.348 0-1.39-.223-2.73-.635-3.985a.75.75 0 0 0-.722-.516l-.143.001c-2.996 0-5.717-1.17-7.734-3.08Zm3.094 8.016a.75.75 0 1 0-1.22-.872l-3.236 4.53L9.53 12.22a.75.75 0 0 0-1.06 1.06l2.25 2.25a.75.75 0 0 0 1.14-.094l3.75-5.25Z" clip-rule="evenodd"/></svg>
        @else
            <svg viewBox="0 0 20 20" fill="currentColor" class="w-5 h-5 shrink-0 {{ $banner['accent'] }}" aria-hidden="true"><path fill-rule="evenodd" d="M8.485 2.495c.673-1.167 2.357-1.167 3.03 0l6.28 10.875c.673 1.167-.17 2.625-1.516 2.625H3.72c-1.347 0-2.189-1.458-1.515-2.625L8.485 2.495ZM10 5a.75.75 0 0 1 .75.75v3.5a.75.75 0 0 1-1.5 0v-3.5A.75.75 0 0 1 10 5Zm0 9a1 1 0 1 0 0-2 1 1 0 0 0 0 2Z" clip-rule="evenodd"/></svg>
        @endif
        <span class="flex-1 min-w-0 leading-tight">
            <strong class="block text-[14px] font-semibold">{{ $banner['title'] }}</strong>
            <span class="block mt-0.5 text-[12.5px] opacity-80 truncate">{{ $banner['lead'] }}</span>
        </span>
        <span class="shrink-0 inline-flex items-center gap-1 h-7 px-2 rounded-md bg-white/70 ring-1 ring-current/15 font-mono text-[12px] font-semibold tracking-wider">{{ $qr_code }}</span>
        <svg viewBox="0 0 20 20" fill="currentColor" class="w-4 h-4 shrink-0 opacity-60 transition-transform group-open/vf:rotate-180" aria-hidden="true"><path fill-rule="evenodd" d="M5.22 8.22a.75.75 0 0 1 1.06 0L10 11.94l3.72-3.72a.75.75 0 1 1 1.06 1.06l-4.25 4.25a.75.75 0 0 1-1.06 0L5.22 9.28a.75.75 0 0 1 0-1.06Z" clip-rule="evenodd"/></svg>
    </summary>

    <div class="px-4 pb-3.5 text-[13px] leading-relaxed">
        <p class="opacity-85">{{ $banner['note'] }}</p>

        <dl class="mt-2.5 rounded-lg bg-white/75 ring-1 ring-current/10 divide-y divide-current/10">
            <div class="flex items-center gap-3 px-3 py-2">
                <dt class="w-32 shrink-0 opacity-70">Mã truy xuất</dt>
                <dd class="flex-1 min-w-0 flex items-center justify-end gap-2">
                    <span class="font-mono font-semibold tracking-wider">{{ $qr_code }}</span>
                    <button type="button" class="inline-flex items-center h-7 px-2 rounded-md text-[12px] font-semibold ring-1 ring-current/20 hover:bg-white"
                            data-code="{{ $qr_code }}"
                            onclick="navigator.clipboard?.writeText(this.dataset.code).then(() => { this.textContent = 'Đã chép'; setTimeout(() => this.textContent = 'Sao chép', 1600) })">Sao chép</button>
                </dd>
            </div>
            @foreach ($rows as $label => $value)
                <div class="flex items-center gap-3 px-3 py-2">
                    <dt class="w-32 shrink-0 opacity-70">{{ $label }}</dt>
                    <dd class="flex-1 min-w-0 text-right font-medium truncate">{{ $value }}</dd>
                </div>
            @endforeach
        </dl>

        @if ($official_host ?? null)
            <p class="mt-2.5 flex gap-2 opacity-85">
                <svg viewBox="0 0 20 20" fill="currentColor" class="w-4 h-4 mt-0.5 shrink-0" aria-hidden="true"><path fill-rule="evenodd" d="M10 1a4.5 4.5 0 0 0-4.5 4.5V9H5a2 2 0 0 0-2 2v6a2 2 0 0 0 2 2h10a2 2 0 0 0 2-2v-6a2 2 0 0 0-2-2h-.5V5.5A4.5 4.5 0 0 0 10 1Zm3 8V5.5a3 3 0 1 0-6 0V9h6Z" clip-rule="evenodd"/></svg>
                <span>Chỉ tin kết quả khi địa chỉ trang là <strong class="font-semibold [overflow-wrap:anywhere]">{{ $official_host }}/p/{{ $qr_code }}</strong>. Trang khác tên miền có thể là giả mạo.</span>
            </p>
        @endif

        <a href="#phan-anh" onclick="document.getElementById('phan-anh').open = true"
           class="mt-2.5 inline-flex items-center gap-1.5 font-semibold underline underline-offset-2 decoration-current/40 hover:decoration-current [&>svg]:w-3.5 [&>svg]:h-3.5">@include('traceability.partials.icon', ['name' => 'flag']) Thấy thông tin chưa đúng? Phản ánh</a>
    </div>
</details>
</div>
