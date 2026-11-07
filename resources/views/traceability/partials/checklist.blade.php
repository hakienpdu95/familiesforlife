<ul class="border-t border-base-300 text-[15px]">
    @foreach ($items as $item)
        <li class="flex items-start gap-3 py-2.5 border-b border-base-300">
            <span @class(['w-5 text-center font-bold shrink-0', 'text-success' => $item['count'] > 0, 'text-base-content/25' => $item['count'] === 0]) aria-hidden="true">{{ $item['count'] > 0 ? '✓' : '—' }}</span>
            <span class="flex-1 min-w-0 {{ $item['count'] > 0 ? '' : 'text-base-content/60' }}">{{ $item['label'] }}</span>
            <span class="text-xs text-base-content/60 shrink-0 pt-0.5">{{ $item['count'] > 0 ? ($item['updated_at'] ? 'Cập nhật '.$item['updated_at'] : 'Đã nộp') : 'Chưa có' }}</span>
        </li>
    @endforeach
</ul>
<p class="mt-2 text-xs text-base-content/60">Hồ sơ gốc do ban quản lý lưu giữ và đối chiếu, không công bố tệp.</p>
