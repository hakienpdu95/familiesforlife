<?php

namespace Modules\Playlist\Contracts;

use Illuminate\Database\Eloquent\Builder;

/**
 * spec/Playlist_Technical_Specification.md §4.3 — hợp đồng mà mọi model muốn "tham gia" làm
 * item của Playlist phải implement. Video/Post implement trực tiếp trên Model của chúng (§4.4/
 * §4.5) — Modules/Playlist không cần biết Video/Post là gì, chỉ gọi qua interface này.
 *
 * scopeSearchablePlaylistItems() CỐ Ý được khai trong interface dù về mặt Eloquent nó là 1
 * "local scope" — bản chất chỉ là 1 public instance method thường (tiền tố `scope` chỉ có ý
 * nghĩa với Eloquent query builder qua __call magic), nên khai báo được như method bình thường.
 * Nhờ vậy, 1 class trong config('playlist.itemables') QUÊN implement sẽ bị PHP báo
 * `Fatal error: Class ... contains 1 abstract method` NGAY khi autoload — bắt lỗi sớm hơn hẳn
 * so với để runtime tự __call() báo "method không tồn tại" hoặc tệ hơn là kết quả rỗng im lặng.
 */
interface PlaylistableContract
{
    public function getPlaylistCardTitle(): string;

    public function getPlaylistCardDescription(): ?string;

    public function getPlaylistCardThumbnailUrl(): ?string;

    /** Link "an toàn" luôn dùng được — trang chi tiết/watch_url. */
    public function getPlaylistCardUrl(): string;

    /**
     * URL nhúng lightbox — CHỈ Video trả khác null (§0). Bài viết trả null → view điều hướng
     * sang getPlaylistCardUrl() thay vì mở modal.
     */
    public function getPlaylistCardEmbedUrl(): ?string;

    /** Nhãn phân loại hiển thị ở badge — "Video" / "Bài viết". */
    public function getPlaylistCardTypeLabel(): string;

    /**
     * Có còn hợp lệ để hiển thị công khai không (is_active/published). Query công khai VÀ
     * SearchPlaylistableItemsAction (phòng thủ lớp 2, §0) đều lọc qua method này.
     */
    public function isPlaylistCardVisible(): bool;

    /**
     * Scope tìm kiếm dùng bởi ô tìm kiếm hợp nhất (§6.4) — mỗi model tự biết search đúng cột/
     * quan hệ của mình (Video theo `name`, PostArticle qua `translations.title`).
     */
    public function scopeSearchablePlaylistItems(Builder $query, string $keyword): void;
}
