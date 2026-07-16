{{-- 1 dòng trong cây danh mục OCOP chính thức — đệ quy tự include cho $item->children (tối đa
     3 cấp theo spec/danhmuc.html: nhóm lớn I-VI → nhóm → phân nhóm). --}}
<tr class="hover">
    <td class="text-center font-mono text-xs {{ $depth === 0 ? 'font-bold' : '' }}">{{ $item->code }}</td>
    <td style="padding-left: {{ 1 + $depth * 1.5 }}rem">
        <span class="{{ $depth === 0 ? 'font-bold uppercase text-sm' : ($depth === 1 ? 'font-medium' : '') }}">
            {{ $item->name }}
        </span>
    </td>
    <td class="text-sm text-base-content/70">{{ $item->authority }}</td>
</tr>
@foreach($item->children as $child)
    @include('ocop::admin.categories._category-tree-row', ['item' => $child, 'depth' => $depth + 1])
@endforeach
