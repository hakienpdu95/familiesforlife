{{-- Dùng chung create/edit — nhận $categoryTree (đệ quy cha/con/cháu... từ GetCategoryTreeHandler),
     $selectedCategoryIds (array id đã tick), $primaryCategoryId (id danh mục chính, nullable).
     Optional: $maxHeightClass (mặc định max-h-56), $size ('sm' mặc định | 'xs' cho sidebar hẹp).
     JS: Modules/Post/resources/assets/js/pages/article-form.js (_setupCategoryPicker) đồng bộ
     hidden input is_primary_category_id + auto-tick checkbox khi bấm ★, gỡ ★ khi bỏ tick. --}}
<input type="hidden" name="is_primary_category_id" value="{{ $primaryCategoryId }}" data-cat-primary-input>
<div class="{{ $maxHeightClass ?? 'max-h-56' }} overflow-y-auto flex flex-col gap-0.5 border border-base-200 rounded-lg p-2"
     data-cat-picker>
    @forelse($categoryTree as $root)
        @include('post::admin.articles._category-picker-row', [
            'item' => $root, 'depth' => 0, 'size' => $size ?? 'sm',
            'selectedCategoryIds' => $selectedCategoryIds, 'primaryCategoryId' => $primaryCategoryId,
        ])
    @empty
        <p class="text-xs text-base-content/30 py-2">Chưa có danh mục nào.</p>
    @endforelse
</div>
