{{-- Dùng chung create/edit — spec/Breaking_News_Ticker_Technical_Specification.md §6.2. --}}
@php
    $defaultEndsAt = now()->addHours((int) config('post.breaking_news.default_duration_hours', 48))->format('Y-m-d H:i:s');
    $selectedArticle = $breakingNews?->article;
    $selectedTitle   = $selectedArticle?->translations->first()?->title;
@endphp

<div class="grid grid-cols-1 xl:grid-cols-[1fr_268px] gap-6 items-start">

    {{-- ── Card chính ──────────────────────────────────────────────── --}}
    <div class="space-y-5">
        <div class="card bg-base-100 shadow-sm border border-base-200">
            <div class="card-body">

                <h2 class="card-title text-base mb-5">
                    <svg class="w-4 h-4 text-error" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 10V3L4 14h7v7l9-11h-7z"/>
                    </svg>
                    Đánh dấu tin nóng
                </h2>

                <div class="space-y-4">

                    <div class="form-control">
                        <label class="label py-0 pb-1.5">
                            <span class="label-text font-medium">Bài viết <span class="text-error">*</span></span>
                        </label>
                        {{-- Tạo mới: chọn NHIỀU bài cùng lúc (article_ids[], multiple) — mỗi bài
                             tạo 1 bản ghi riêng, dùng chung lịch/nhãn điền bên dưới (xem
                             BreakingNewsAdminController::store()). Sửa: đúng 1 bài/1 bản ghi,
                             giữ nguyên article_id đơn — không cho đổi thành nhiều. --}}
                        <select name="{{ $breakingNews ? 'article_id' : 'article_ids[]' }}" id="article-picker"
                                @if(! $breakingNews) multiple @endif
                                class="w-full @error('article_id') select-error @enderror @error('article_ids') select-error @enderror"
                                data-ts-remote-url="{{ route('backend.api.breaking-news.articles.search') }}"
                                data-ts-placeholder="Tìm bài viết theo tiêu đề..."
                                data-active-ids="{{ json_encode($activeArticleIds ?? []) }}">
                            @if($selectedArticle)
                            <option value="{{ $selectedArticle->id }}" selected>{{ $selectedTitle }}</option>
                            @endif
                        </select>
                        @error('article_id')<p class="mt-1 text-xs text-error">{{ $message }}</p>@enderror
                        @error('article_ids')<p class="mt-1 text-xs text-error">{{ $message }}</p>@enderror
                        @if($errors->has('article_ids.*'))
                        <p class="mt-1 text-xs text-error">{{ $errors->first('article_ids.*') }}</p>
                        @endif
                        <p class="text-xs text-base-content/40 mt-1.5">
                            Gõ để tìm — mặc định hiện sẵn 20 bài xuất bản gần đây nhất. Chỉ tìm được bài đã xuất bản.
                            @if(! $breakingNews)
                            Có thể chọn nhiều bài cùng lúc — mỗi bài sẽ tạo 1 tin nóng riêng, dùng chung lịch hiển thị/nhãn ở dưới.
                            @endif
                        </p>
                        <p id="article-picker-warning" class="hidden text-xs text-warning mt-1.5 flex items-center gap-1">
                            <svg class="w-3.5 h-3.5 shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"/>
                            </svg>
                            <span>Có bài đang có 1 tin nóng khác còn hiệu lực — vẫn lưu được, nhưng có thể gây trùng lặp trên ticker.</span>
                        </p>
                    </div>

                    <div class="divider my-1"></div>

                    <div class="form-control">
                        <label class="label py-0 pb-1.5">
                            <span class="label-text font-medium">Tiêu đề hiển thị trên ticker</span>
                            <span class="label-text-alt text-xs text-base-content/40">Để trống = dùng tiêu đề thật của bài</span>
                        </label>
                        <input type="text" name="headline_override" value="{{ old('headline_override', $breakingNews?->headline_override) }}"
                               class="input input-bordered input-sm w-full @error('headline_override') input-error @enderror"
                               maxlength="300" placeholder="Tiêu đề ngắn, gây chú ý hơn (tuỳ chọn)">
                        @error('headline_override')<p class="mt-1 text-xs text-error">{{ $message }}</p>@enderror
                    </div>

                    <div class="form-control">
                        <label class="label py-0 pb-1.5">
                            <span class="label-text font-medium">Nhãn badge</span>
                            <span class="label-text-alt text-xs text-base-content/40">Để trống = "{{ config('post.breaking_news.default_badge_label', 'NÓNG') }}"</span>
                        </label>
                        <input type="text" name="badge_label" list="badge-label-suggestions"
                               value="{{ old('badge_label', $breakingNews?->badge_label) }}"
                               class="input input-bordered input-sm w-full @error('badge_label') input-error @enderror"
                               maxlength="40">
                        <datalist id="badge-label-suggestions">
                            <option value="NÓNG"><option value="KHẨN"><option value="MỚI">
                        </datalist>
                        @error('badge_label')<p class="mt-1 text-xs text-error">{{ $message }}</p>@enderror
                    </div>

                    <div class="divider my-1"></div>

                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                        <div class="form-control">
                            <label class="label py-0 pb-1.5">
                                <span class="label-text font-medium">Bắt đầu hiển thị</span>
                                <span class="label-text-alt text-xs text-base-content/40">Để trống = hiển thị ngay</span>
                            </label>
                            <input type="text" name="starts_at" id="fp-starts-at" data-fp-mode="datetime"
                                   value="{{ old('starts_at', $breakingNews?->starts_at?->format('Y-m-d H:i:s')) }}"
                                   class="input input-bordered input-sm w-full fp-init @error('starts_at') input-error @enderror">
                            @error('starts_at')<p class="mt-1 text-xs text-error">{{ $message }}</p>@enderror
                        </div>
                        <div class="form-control">
                            <label class="label py-0 pb-1.5">
                                <span class="label-text font-medium">Kết thúc hiển thị</span>
                                <span class="label-text-alt text-xs text-base-content/40">Gợi ý +{{ config('post.breaking_news.default_duration_hours', 48) }}h, sửa được</span>
                            </label>
                            <input type="text" name="ends_at" id="fp-ends-at" data-fp-mode="datetime"
                                   value="{{ old('ends_at', $breakingNews?->ends_at?->format('Y-m-d H:i:s') ?? (! $breakingNews ? $defaultEndsAt : '')) }}"
                                   class="input input-bordered input-sm w-full fp-init @error('ends_at') input-error @enderror">
                            @error('ends_at')<p class="mt-1 text-xs text-error">{{ $message }}</p>@enderror
                        </div>
                    </div>

                    <div class="form-control">
                        <label class="label py-0 pb-1.5">
                            <span class="label-text font-medium">Thứ tự trong vòng xoay</span>
                            <span class="label-text-alt text-xs text-base-content/40">Nhỏ hơn hiện trước</span>
                        </label>
                        <input type="number" name="sort_order" min="0"
                               value="{{ old('sort_order', $breakingNews?->sort_order ?? 0) }}"
                               class="input input-bordered input-sm w-full @error('sort_order') input-error @enderror">
                        @error('sort_order')<p class="mt-1 text-xs text-error">{{ $message }}</p>@enderror
                    </div>

                    <label class="flex items-start gap-2.5 cursor-pointer select-none group">
                        <input type="hidden" name="is_active" value="0">
                        <input type="checkbox" name="is_active" value="1"
                               class="checkbox checkbox-sm checkbox-primary mt-0.5 shrink-0"
                               {{ old('is_active', $breakingNews?->is_active ?? true) ? 'checked' : '' }}>
                        <div>
                            <span class="text-sm font-medium group-hover:text-primary transition-colors">Hiển thị</span>
                            <p class="text-xs text-base-content/50 mt-0.5">Tắt để tạm gỡ khỏi ticker, không cần xoá</p>
                        </div>
                    </label>

                </div>
            </div>
        </div>
    </div>

    {{-- ── Sidebar ──────────────────────────────────────────────────── --}}
    <div class="xl:sticky xl:top-4 space-y-4">
        <div class="card bg-base-100 shadow-sm border border-base-200">
            <div class="card-body p-4">

                <p class="text-xs font-semibold text-base-content/40 uppercase tracking-wide mb-3">
                    Xuất bản
                </p>

                <div class="flex gap-2">
                    <a href="{{ route('backend.post.breaking-news.items.index') }}" class="btn btn-ghost btn-sm flex-1">Hủy</a>
                    <button type="submit" class="btn btn-primary btn-sm flex-1 gap-1.5">
                        <svg class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/>
                        </svg>
                        {{ $breakingNews ? 'Lưu thay đổi' : 'Đánh dấu nóng' }}
                    </button>
                </div>

                <p class="text-center text-xs text-base-content/30 mt-2.5">
                    <span class="text-error">*</span> là trường bắt buộc
                </p>

            </div>
        </div>
    </div>

</div>
