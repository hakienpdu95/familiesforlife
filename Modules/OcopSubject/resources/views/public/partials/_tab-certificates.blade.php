@forelse($certificates as $group)
<div class="mb-8 last:mb-0">
    <h3 class="mb-3 text-base font-semibold text-[#333]">{{ $group['label'] }}</h3>
    <div class="grid grid-cols-2 md:grid-cols-3 lg:grid-cols-4 gap-4">
        @foreach($group['files'] as $file)
        <a href="{{ $file['url'] }}" target="_blank" rel="noopener"
           class="group flex flex-col overflow-hidden rounded-sm border border-[#e0e0e0] bg-white transition hover:shadow-md">
            <div class="flex aspect-[3/4] items-center justify-center overflow-hidden bg-[#f8f9fa]">
                @if($file['is_image'])
                <img src="{{ $file['url'] }}" alt="{{ $group['label'] }}" class="h-full w-full object-contain transition group-hover:scale-105" loading="lazy">
                @else
                <div class="flex flex-col items-center gap-2 text-[#c0392b]">
                    <svg class="h-14 w-14" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M7 21h10a2 2 0 002-2V9.414a1 1 0 00-.293-.707l-5.414-5.414A1 1 0 0012.586 3H7a2 2 0 00-2 2v14a2 2 0 002 2z"/></svg>
                    <span class="text-xs font-bold">PDF</span>
                </div>
                @endif
            </div>
            <div class="border-t border-[#e0e0e0] px-3 py-2">
                <p class="line-clamp-1 text-sm text-[#333] group-hover:text-[#117a3a]">{{ $file['name'] }}</p>
                <p class="text-xs text-[#999]">{{ $file['size'] }}</p>
            </div>
        </a>
        @endforeach
    </div>
</div>
@empty
<p class="py-10 text-center text-sm text-base-content/50">Chủ thể chưa công bố giấy chứng nhận nào.</p>
@endforelse
