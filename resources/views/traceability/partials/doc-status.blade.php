<section class="px-4 mt-5">
    <h2 class="text-sm font-bold mb-2 flex items-center gap-1.5"><i class="fa-solid fa-award text-primary" aria-hidden="true"></i> {{ $heading }}</h2>
    <ul class="rounded-xl border border-base-200 divide-y divide-base-200">
        @foreach ($docs as $doc)
            <li class="flex items-start gap-3 px-3 py-2.5">
                @if ($doc['count'] > 0)
                    <i class="fa-solid fa-circle-check text-green-600 text-lg mt-0.5" aria-hidden="true"></i>
                @else
                    <i class="fa-solid fa-circle text-base-300 text-lg mt-0.5" aria-hidden="true"></i>
                @endif
                <div class="min-w-0 flex-1">
                    <p class="text-sm font-medium">{{ $doc['label'] }}</p>
                    <p class="text-xs {{ $doc['count'] > 0 ? 'text-green-700' : 'text-base-content/40' }}">
                        @if ($doc['count'] > 0)
                            Đã nộp {{ $doc['count'] }} tệp{{ $doc['updated_at'] ? ' · cập nhật '.$doc['updated_at'] : '' }}
                        @else
                            Chưa có hồ sơ
                        @endif
                    </p>
                </div>
            </li>
        @endforeach
    </ul>
    <p class="text-[11px] text-base-content/40 mt-1.5">Hồ sơ gốc được lưu trữ và đối chiếu bởi ban quản lý — không công khai tệp để bảo mật.</p>
</section>
