{{-- Render 1 hàng của cây menu — dùng chung cho cả 3 cấp (depth 0/1/2), thụt lề theo $depth.
     $siblings = collection cùng cấp (cùng parent_id) để tính nút lên/xuống — spec/Menu_Navigation_Technical_Specification.md §6.2. --}}
@php
    $index = $siblings->search(fn ($x) => $x->id === $item->id);
    $prev  = $index > 0 ? $siblings[$index - 1] : null;
    $next  = $index < $siblings->count() - 1 ? $siblings[$index + 1] : null;
@endphp
<tr class="hover">
    <td>
        <div class="flex items-center" style="padding-left: {{ $depth * 1.5 }}rem">
            @if($depth > 0)<span class="text-base-content/25 mr-1.5">└</span>@endif
            @if($item->icon)<i class="{{ $item->icon }} mr-1.5 text-base-content/50"></i>@endif
            <span class="font-medium text-sm">{{ $item->label }}</span>
        </div>
    </td>
    <td class="text-center">
        <span class="badge badge-sm badge-ghost">{{ config('menu.locations')[$item->location] ?? $item->location }}</span>
    </td>
    <td class="text-sm text-base-content/60">
        @switch($item->link_type->value)
            @case('category')
                Danh mục: <span class="font-medium text-base-content">{{ $item->category?->name ?? '— (đã xoá) —' }}</span>
                @break
            @case('url')
                <span class="font-mono text-xs">{{ \Illuminate\Support\Str::limit($item->url, 40) }}</span>
                @if($item->open_in_new_tab)<span class="badge badge-xs badge-ghost ml-1">tab mới</span>@endif
                @break
            @default
                <span class="text-base-content/30">— chỉ mở submenu —</span>
        @endswitch
    </td>
    <td class="text-center text-sm">
        <div class="flex items-center justify-center gap-0.5">
            <form method="POST" action="{{ route('backend.menu.items.reorder') }}">
                @csrf
                <input type="hidden" name="order[{{ $item->id }}]" value="{{ $prev->sort_order ?? $item->sort_order }}">
                <input type="hidden" name="order[{{ $prev->id ?? '' }}]" value="{{ $item->sort_order }}">
                <button class="btn btn-ghost btn-xs btn-square" title="Lên" {{ $prev ? '' : 'disabled' }}>
                    <svg class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 15l7-7 7 7"/></svg>
                </button>
            </form>
            <form method="POST" action="{{ route('backend.menu.items.reorder') }}">
                @csrf
                <input type="hidden" name="order[{{ $item->id }}]" value="{{ $next->sort_order ?? $item->sort_order }}">
                <input type="hidden" name="order[{{ $next->id ?? '' }}]" value="{{ $item->sort_order }}">
                <button class="btn btn-ghost btn-xs btn-square" title="Xuống" {{ $next ? '' : 'disabled' }}>
                    <svg class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/></svg>
                </button>
            </form>
        </div>
    </td>
    <td class="text-center">
        <span class="badge badge-sm {{ $item->is_active ? 'badge-success' : 'badge-ghost' }}">
            {{ $item->is_active ? 'Hiện' : 'Ẩn' }}
        </span>
    </td>
    <td>
        <div class="flex gap-1">
            @can('update', $item)
            <a href="{{ route('backend.menu.items.edit', $item) }}" class="btn btn-ghost btn-xs btn-square" title="Sửa">
                <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"/>
                </svg>
            </a>
            @endcan
            @can('delete', $item)
            <form method="POST" action="{{ route('backend.menu.items.destroy', $item) }}"
                  onsubmit="return confirm('Xoá mục menu &quot;{{ $item->label }}&quot;?')">
                @csrf @method('DELETE')
                <button class="btn btn-ghost btn-xs btn-square text-error" title="Xoá">
                    <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/>
                    </svg>
                </button>
            </form>
            @endcan
        </div>
    </td>
</tr>
