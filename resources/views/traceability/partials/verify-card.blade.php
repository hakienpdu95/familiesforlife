@php
    $states = [
        'valid' => ['box' => 'border-green-500 bg-green-50 text-green-900', 'iconBox' => 'bg-green-500', 'icon' => 'fa-circle-check', 'title' => 'Mã hợp lệ', 'text' => 'Thông tin được cập nhật trực tiếp từ hệ thống.'],
        'archived' => ['box' => 'border-orange-500 bg-orange-50 text-orange-900', 'iconBox' => 'bg-orange-500', 'icon' => 'fa-box-archive', 'title' => 'Bản lưu trữ', 'text' => $archive_text],
        'revoked' => ['box' => 'border-red-500 bg-red-50 text-red-900', 'iconBox' => 'bg-red-500', 'icon' => 'fa-ban', 'title' => 'Mã đã bị thu hồi', 'text' => 'Mã QR này không còn giá trị xác thực'.($revoked_at ? ' kể từ ngày '.$revoked_at : '').'. Thông tin bên dưới chỉ mang tính tham khảo.'],
    ];
    $state = $states[$status];
@endphp
<section class="mx-4 mt-4 rounded-xl border-l-4 {{ $state['box'] }} px-3 py-3" aria-label="Trạng thái xác thực">
    <div class="flex items-start gap-3">
        <span class="flex items-center justify-center w-10 h-10 rounded-full {{ $state['iconBox'] }} text-white shrink-0">
            <i class="fa-solid {{ $state['icon'] }} text-lg" aria-hidden="true"></i>
        </span>
        <div class="min-w-0">
            <p class="font-bold text-base leading-tight">{{ $state['title'] }}</p>
            <p class="text-xs mt-0.5 opacity-80">{{ $state['text'] }}</p>
        </div>
    </div>
</section>
