{{-- 1 dòng trong cây danh mục bài viết — đệ quy tự include cho $item->children (không giới hạn
     số cấp, dữ liệu do GetCategoryTreeHandler dựng sẵn qua setRelation, không lazy-load). --}}
<tr class="hover">
    <td style="padding-left: {{ $depth * 1.5 }}rem">
        @if($depth > 0)<span class="text-base-content/25 text-xs mr-1">└</span>@endif
        <span class="inline-block size-2.5 rounded-full mr-1.5 align-middle" style="background:{{ $item->color_hex ?? '#94a3b8' }}"></span>
        <span class="font-medium text-sm">{{ $item->name }}</span>
        <div class="text-xs text-base-content/40 font-mono" style="margin-left: {{ $depth * 1.5 + 1 }}rem">{{ $item->slug }}</div>
    </td>
    <td class="text-center text-sm">{{ $item->articles_count }}</td>
    <td class="text-center">
        <span class="badge badge-sm {{ $item->is_active ? 'badge-success' : 'badge-ghost' }}">
            {{ $item->is_active ? 'Hiện' : 'Ẩn' }}
        </span>
    </td>
    <td>
        <div class="flex gap-1">
            @can('update', $item)
            <a href="{{ route('backend.post.categories.edit', $item) }}" class="btn btn-ghost btn-xs btn-square" title="Sửa">
                <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"/>
                </svg>
            </a>
            @endcan
            @can('delete', $item)
            <form method="POST" action="{{ route('backend.post.categories.destroy', $item) }}"
                  onsubmit="return confirm('Xoá danh mục &quot;{{ $item->name }}&quot;?')">
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
@foreach($item->children as $child)
    @include('post::admin.categories._category-tree-row', ['item' => $child, 'depth' => $depth + 1])
@endforeach
