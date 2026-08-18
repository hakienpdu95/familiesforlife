{{-- Dùng chung create/edit — spec/Playlist_Technical_Specification.md §6.2/§6.3.
     Quản lý ITEM (thêm/gỡ/sắp xếp) KHÔNG nằm trong form này — chỉ khả dụng ở trang edit (cần
     $playlist->id đã tồn tại), xem admin/playlists/edit.blade.php. --}}
<div class="grid grid-cols-1 xl:grid-cols-[1fr_300px] gap-6 items-start">

    {{-- ── Card chính ──────────────────────────────────────────────── --}}
    <div class="space-y-5">
        <div class="card bg-base-100 shadow-sm border border-base-200">
            <div class="card-body">

                <h2 class="card-title text-base mb-5">
                    <svg class="w-4 h-4 text-primary" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 19V6l12-2v13M9 19c0 1.105-1.343 2-3 2s-3-.895-3-2 1.343-2 3-2 3 .895 3 2zm12-2c0 1.105-1.343 2-3 2s-3-.895-3-2 1.343-2 3-2 3 .895 3 2zM9 10l12-2"/>
                    </svg>
                    Thông tin playlist
                </h2>

                <div class="space-y-4">

                    <div class="form-control">
                        <label class="label py-0 pb-1.5">
                            <span class="label-text font-medium">Tên <span class="text-error">*</span></span>
                            <span class="label-text-alt text-xs text-base-content/40">Đưa từ khoá chính lên ĐẦU tên (VD: "Ăn dặm cho bé 6 tháng: Hướng dẫn từ A-Z")</span>
                        </label>
                        <input type="text" name="name" value="{{ old('name', $playlist?->name) }}"
                               class="input input-bordered input-sm w-full @error('name') input-error @enderror"
                               maxlength="255" required>
                        @error('name')<p class="mt-1 text-xs text-error">{{ $message }}</p>@enderror
                    </div>

                    <div class="form-control">
                        <label class="label py-0 pb-1.5">
                            <span class="label-text font-medium">Slug <span class="text-error">*</span></span>
                            <span class="label-text-alt text-xs text-base-content/40">Dùng trong URL công khai /playlists/&lt;slug&gt;</span>
                        </label>
                        <input type="text" name="slug" value="{{ old('slug', $playlist?->slug) }}"
                               class="input input-bordered input-sm w-full font-mono @error('slug') input-error @enderror"
                               maxlength="255" pattern="[A-Za-z0-9_\-]+" required>
                        @error('slug')<p class="mt-1 text-xs text-error">{{ $message }}</p>@enderror
                    </div>

                    <div class="form-control">
                        <label class="label py-0 pb-1.5">
                            <span class="label-text font-medium">Mô tả</span>
                        </label>
                        <textarea name="description" rows="3" maxlength="2000"
                                  class="textarea textarea-bordered textarea-sm w-full @error('description') textarea-error @enderror"
                        >{{ old('description', $playlist?->description) }}</textarea>
                        @error('description')<p class="mt-1 text-xs text-error">{{ $message }}</p>@enderror
                        {{-- Gợi ý cấu trúc mô tả chuẩn SEO — spec/tech.md "Cách viết Tiêu đề và Mô tả
                             chuẩn chỉnh cho Playlist" (§4), phần transfer được sang playlist của
                             site (không phụ thuộc thuật toán YouTube). Chỉ là text tĩnh tham khảo,
                             không bắt buộc — xem thêm nút "Gợi ý bằng AI" bên dưới nếu cần viết mới. --}}
                        <p class="text-xs text-base-content/40 mt-1.5 leading-relaxed">
                            Gợi ý cấu trúc: (1) câu mở lặp lại từ khoá chính tự nhiên — (2) nêu rõ playlist giúp giải quyết vấn đề gì / học được kỹ năng gì — (3) liệt kê nhanh các chủ đề nổi bật có trong playlist — (4) 1 câu kêu gọi hành động ngắn.
                        </p>
                    </div>

                    <div class="form-control">
                        <label class="label py-0 pb-1.5">
                            <span class="label-text font-medium">Ảnh đại diện (URL)</span>
                            <span class="label-text-alt text-xs text-base-content/40">Bỏ trống để tự lấy thumbnail item đầu tiên</span>
                        </label>
                        <input type="text" name="cover_image_url" value="{{ old('cover_image_url', $playlist?->cover_image_url) }}"
                               class="input input-bordered input-sm w-full @error('cover_image_url') input-error @enderror"
                               maxlength="2048" placeholder="https://...">
                        @error('cover_image_url')<p class="mt-1 text-xs text-error">{{ $message }}</p>@enderror
                    </div>

                    <div class="divider my-1">SEO</div>

                    <div class="form-control">
                        <label class="label py-0 pb-1.5">
                            <span class="label-text font-medium">Meta title</span>
                            <span class="label-text-alt text-xs text-base-content/40">Bỏ trống để dùng Tên</span>
                        </label>
                        <input type="text" name="meta_title" value="{{ old('meta_title', $playlist?->meta_title) }}"
                               class="input input-bordered input-sm w-full @error('meta_title') input-error @enderror"
                               maxlength="255">
                        @error('meta_title')<p class="mt-1 text-xs text-error">{{ $message }}</p>@enderror
                    </div>

                    <div class="form-control">
                        <label class="label py-0 pb-1.5">
                            <span class="label-text font-medium">Meta description</span>
                            <span class="label-text-alt text-xs text-base-content/40">Bỏ trống để dùng Mô tả</span>
                        </label>
                        <textarea name="meta_description" rows="2" maxlength="500"
                                  class="textarea textarea-bordered textarea-sm w-full @error('meta_description') textarea-error @enderror"
                        >{{ old('meta_description', $playlist?->meta_description) }}</textarea>
                        @error('meta_description')<p class="mt-1 text-xs text-error">{{ $message }}</p>@enderror
                    </div>

                    <div class="divider my-1"></div>

                    <div class="form-control">
                        <label class="label py-0 pb-1.5">
                            <span class="label-text font-medium">Thứ tự hiển thị</span>
                            <span class="label-text-alt text-xs text-base-content/40">Thứ tự CỦA PLAYLIST ở trang /playlists (khác thứ tự item bên trong)</span>
                        </label>
                        <input type="number" name="sort_order" min="0"
                               value="{{ old('sort_order', $playlist?->sort_order ?? 0) }}"
                               class="input input-bordered input-sm w-full @error('sort_order') input-error @enderror">
                        @error('sort_order')<p class="mt-1 text-xs text-error">{{ $message }}</p>@enderror
                    </div>

                    <label class="flex items-start gap-2.5 cursor-pointer select-none group">
                        <input type="hidden" name="is_active" value="0">
                        <input type="checkbox" name="is_active" value="1"
                               class="checkbox checkbox-sm checkbox-primary mt-0.5 shrink-0"
                               {{ old('is_active', $playlist?->is_active ?? true) ? 'checked' : '' }}>
                        <div>
                            <span class="text-sm font-medium group-hover:text-primary transition-colors">Hiển thị playlist</span>
                            <p class="text-xs text-base-content/50 mt-0.5">Tắt để tạm ẩn khỏi trang công khai, không cần xoá</p>
                        </div>
                    </label>

                </div>
            </div>
        </div>
    </div>

    {{-- ── Sidebar ──────────────────────────────────────────────────── --}}
    <div class="xl:sticky xl:top-4 space-y-4">
        <div class="card bg-base-100 shadow-sm border border-base-200">
            <div class="card-body p-3">

                <p class="text-xs font-semibold text-base-content/40 uppercase tracking-wide mb-3">
                    Xuất bản
                </p>

                <div class="flex gap-2">
                    <a href="{{ route('backend.playlist.items.index') }}" class="btn btn-ghost btn-sm flex-1">Hủy</a>
                    <button type="submit" class="btn btn-primary btn-sm flex-1 gap-1.5">
                        <svg class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/>
                        </svg>
                        {{ $playlist ? 'Lưu thay đổi' : 'Tạo mới' }}
                    </button>
                </div>

                @unless($playlist)
                <p class="text-xs text-base-content/40 mt-2.5 leading-relaxed">
                    Sau khi tạo, bạn sẽ được đưa sang màn hình thêm Video/Bài viết vào playlist.
                </p>
                @endunless

                <p class="text-center text-xs text-base-content/30 mt-2.5">
                    <span class="text-error">*</span> là trường bắt buộc
                </p>

            </div>
        </div>
    </div>

</div>
