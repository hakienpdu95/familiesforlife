{{-- 1 dòng trong cây danh mục — đệ quy tự include chính nó cho $item->children, không giới hạn
     số cấp (khác Modules/Menu vốn unroll thủ công tối đa 3 cấp). --}}
@php
    $size      = $size ?? 'sm';
    $textSize  = $size === 'xs' ? 'text-xs' : 'text-sm';
    $checkSize = $size === 'xs' ? 'checkbox-xs' : 'checkbox-sm';
    $starSize  = $size === 'xs' ? 'text-base' : 'text-lg';
    $isPrimary = (string) ($primaryCategoryId ?? '') === (string) $item->id;
@endphp
{{-- Nút ★ PHẢI nằm ngoài <label> của checkbox — click vào bất kỳ đâu trong <label> (kể cả 1
     <button> lồng bên trong) đều bị trình duyệt tự "ké" thêm 1 click vào checkbox được gắn với
     label đó, làm checkbox tự tick/bỏ tick ngoài ý muốn ngay sau khi JS vừa set xong. --}}
<div class="flex items-center gap-2 {{ $textSize }} py-1 px-1.5 rounded hover:bg-base-200/60 transition-colors"
     style="padding-left: {{ $depth * 1.25 }}rem">
    @if($depth > 0)
    <span class="text-base-content/25 text-xs shrink-0">└</span>
    @endif
    <label class="flex items-center gap-2 cursor-pointer flex-1 min-w-0">
        <input type="checkbox" name="category_ids[]" value="{{ $item->id }}"
               class="checkbox {{ $checkSize }} shrink-0" data-cat-check="{{ $item->id }}"
               {{ in_array($item->id, $selectedCategoryIds) ? 'checked' : '' }}>
        <span class="flex-1 truncate {{ $depth === 0 ? 'font-medium' : '' }}">{{ $item->name }}</span>
    </label>
    <button type="button" data-cat-star="{{ $item->id }}"
            class="shrink-0 {{ $starSize }} leading-none transition-colors {{ $isPrimary ? 'text-warning' : 'text-base-content/20 hover:text-base-content/40' }}"
            title="Đặt &quot;{{ $item->name }}&quot; làm danh mục chính"
            aria-label="Đặt {{ $item->name }} làm danh mục chính" aria-pressed="{{ $isPrimary ? 'true' : 'false' }}">★</button>
</div>
@foreach($item->children as $child)
    @include('post::admin.articles._category-picker-row', [
        'item' => $child, 'depth' => $depth + 1, 'size' => $size,
        'selectedCategoryIds' => $selectedCategoryIds, 'primaryCategoryId' => $primaryCategoryId,
    ])
@endforeach
