{{-- Dùng chung create/edit — spec/Video_Management_Technical_Specification.md §6.6.
     video_url/embed_code đều nullable — chỉ cần điền ĐÚNG 1 trong 2, hệ thống tự nhận diện
     video YouTube (§0/§5.2). Không có upload ảnh nào trong form (khác Banner) — thumbnail
     luôn tự suy ra từ youtube_video_id đã lưu (§5.3). --}}
<div class="grid grid-cols-1 xl:grid-cols-[1fr_300px] gap-6 items-start">

    {{-- ── Card chính ──────────────────────────────────────────────── --}}
    <div class="space-y-5">
        <div class="card bg-base-100 shadow-sm border border-base-200">
            <div class="card-body">

                <h2 class="card-title text-base mb-5">
                    <svg class="w-4 h-4 text-primary" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 10l4.553-2.276A1 1 0 0121 8.618v6.764a1 1 0 01-1.447.894L15 14M5 18h8a2 2 0 002-2V8a2 2 0 00-2-2H5a2 2 0 00-2 2v8a2 2 0 002 2z"/>
                    </svg>
                    Thông tin video
                </h2>

                <div class="space-y-4">

                    @if($video)
                    <div class="form-control">
                        <label class="label py-0 pb-1.5">
                            <span class="label-text font-medium">Ảnh đại diện hiện tại</span>
                            <span class="label-text-alt text-xs text-base-content/40">Tự lấy từ YouTube</span>
                        </label>
                        {{-- Bắt đầu bằng bản an toàn (hqdefault), JS tự nâng lên Full HD nếu video có bản HD thật — xem window.videoUpgradeThumbnails trong video.js. --}}
                        <img src="{{ $video->thumbnail_url }}" data-thumb-hd="{{ $video->thumbnail_hd_url }}"
                             alt="{{ $video->name }}"
                             class="h-32 w-auto rounded border border-base-300 object-cover">
                    </div>
                    <div class="divider my-1"></div>
                    @endif

                    <div class="form-control">
                        <label class="label py-0 pb-1.5">
                            <span class="label-text font-medium">Tên <span class="text-error">*</span></span>
                        </label>
                        <input type="text" name="name" value="{{ old('name', $video?->name) }}"
                               class="input input-bordered input-sm w-full @error('name') input-error @enderror"
                               maxlength="255" required>
                        @error('name')<p class="mt-1 text-xs text-error">{{ $message }}</p>@enderror
                    </div>

                    <div class="form-control">
                        <label class="label py-0 pb-1.5">
                            <span class="label-text font-medium">Mô tả</span>
                        </label>
                        <textarea name="description" rows="3" maxlength="2000"
                                  class="textarea textarea-bordered textarea-sm w-full @error('description') textarea-error @enderror"
                        >{{ old('description', $video?->description) }}</textarea>
                        @error('description')<p class="mt-1 text-xs text-error">{{ $message }}</p>@enderror
                    </div>

                    <div class="divider my-1"></div>

                    <div class="form-control">
                        <label class="label py-0 pb-1.5">
                            <span class="label-text font-medium">Link URL Video</span>
                            <span class="label-text-alt text-xs text-base-content/40">Chỉ cần điền 1 trong 2 ô bên dưới</span>
                        </label>
                        <input type="text" name="video_url" value="{{ old('video_url', $video?->video_url) }}"
                               class="input input-bordered input-sm w-full @error('video_url') input-error @enderror"
                               maxlength="2048" placeholder="https://www.youtube.com/watch?v=...">
                        @error('video_url')<p class="mt-1 text-xs text-error">{{ $message }}</p>@enderror
                    </div>

                    <div class="form-control">
                        <label class="label py-0 pb-1.5">
                            <span class="label-text font-medium">Mã Embed video YouTube</span>
                        </label>
                        <textarea name="embed_code" rows="3" maxlength="2000"
                                  class="textarea textarea-bordered textarea-sm w-full font-mono text-xs @error('embed_code') textarea-error @enderror"
                                  placeholder="Dán URL video hoặc mã nhúng (Share &rarr; Embed) từ YouTube"
                        >{{ old('embed_code', $video?->embed_code) }}</textarea>
                        @error('embed_code')<p class="mt-1 text-xs text-error">{{ $message }}</p>@enderror
                        <p class="text-xs text-base-content/40 mt-1.5">
                            Hệ thống tự nhận diện video từ URL hoặc mã nhúng — không cần tự cắt lấy ID.
                        </p>
                    </div>

                    <div class="divider my-1"></div>

                    <div class="form-control">
                        <label class="label py-0 pb-1.5">
                            <span class="label-text font-medium">Thứ tự hiển thị</span>
                        </label>
                        <input type="number" name="sort_order" min="0"
                               value="{{ old('sort_order', $video?->sort_order ?? 0) }}"
                               class="input input-bordered input-sm w-full @error('sort_order') input-error @enderror">
                        @error('sort_order')<p class="mt-1 text-xs text-error">{{ $message }}</p>@enderror
                    </div>

                    <label class="flex items-start gap-2.5 cursor-pointer select-none group">
                        <input type="hidden" name="is_active" value="0">
                        <input type="checkbox" name="is_active" value="1"
                               class="checkbox checkbox-sm checkbox-primary mt-0.5 shrink-0"
                               {{ old('is_active', $video?->is_active ?? true) ? 'checked' : '' }}>
                        <div>
                            <span class="text-sm font-medium group-hover:text-primary transition-colors">Hiển thị video</span>
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
                    <a href="{{ route('backend.video.items.index') }}" class="btn btn-ghost btn-sm flex-1">Hủy</a>
                    <button type="submit" class="btn btn-primary btn-sm flex-1 gap-1.5">
                        <svg class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/>
                        </svg>
                        {{ $video ? 'Lưu thay đổi' : 'Tạo mới' }}
                    </button>
                </div>

                <p class="text-center text-xs text-base-content/30 mt-2.5">
                    <span class="text-error">*</span> là trường bắt buộc
                </p>

            </div>
        </div>
    </div>

</div>
