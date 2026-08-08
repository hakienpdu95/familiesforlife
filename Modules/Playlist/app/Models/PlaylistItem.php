<?php

namespace Modules\Playlist\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphTo;
use Modules\Playlist\Contracts\PlaylistableContract;
use Spatie\Activitylog\Models\Concerns\LogsActivity;
use Spatie\Activitylog\Support\LogOptions;

/**
 * spec/Playlist_Technical_Specification.md §4.2 — Model tường minh (KHÔNG phải pivot ẩn qua
 * belongsToMany) vì cần cột phụ sort_order riêng trong từng playlist + cần model để gọi
 * ->with('itemable'). $itemable_type là ALIAS ngắn (vd "video", "post_article") đăng ký qua
 * Relation::morphMap() trong PlaylistServiceProvider::boot() — KHÔNG phải FQCN thô.
 */
class PlaylistItem extends Model
{
    use LogsActivity;

    protected $fillable = ['playlist_id', 'itemable_type', 'itemable_id', 'sort_order'];

    protected $casts = ['sort_order' => 'integer'];

    /**
     * Chỉ có ý nghĩa log ở 2 sự kiện tạo/xoá (attach/detach) — 1 item không có "sửa thông tin"
     * nào khác ngoài sort_order (đổi qua ReorderPlaylistItemsAction hàng loạt, không đáng ghi
     * log từng dòng).
     */
    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->logFillable()
            ->logOnlyDirty()
            ->dontLogEmptyChanges();
    }

    // ── Relationships ────────────────────────────────────────────────

    public function playlist(): BelongsTo
    {
        return $this->belongsTo(Playlist::class);
    }

    /**
     * morphTo() eager-load ('itemable') tự gom các hàng theo từng loại rồi query 1 lần/loại
     * (hành vi mặc định của Eloquent, không cần code thêm) — xem MorphTo::morphWith() ở
     * GetPlaylistForPublicHandler (§7.4) để tối ưu thêm quan hệ lồng bên trong từng loại.
     */
    public function itemable(): MorphTo
    {
        return $this->morphTo();
    }

    // ── Accessors ────────────────────────────────────────────────────

    /** Ép kiểu về contract — null nếu item mồ côi (nguồn đã xoá cứng). */
    public function getResolvedItemableAttribute(): ?PlaylistableContract
    {
        return $this->itemable instanceof PlaylistableContract ? $this->itemable : null;
    }

    /**
     * Điểm hội tụ null-discipline (§0 — sửa sau review v1.0): null nếu mồ côi HOẶC còn tồn tại
     * nhưng đang ẩn/chưa publish (isPlaylistCardVisible() === false). Mọi nơi hiển thị công khai
     * PHẢI đọc qua property này thay vì tự gọi resolved_itemable + isPlaylistCardVisible() riêng
     * lẻ — giảm rủi ro 1 chỗ quên gây lỗi production.
     */
    public function getVisibleItemableAttribute(): ?PlaylistableContract
    {
        $itemable = $this->resolved_itemable;

        return $itemable?->isPlaylistCardVisible() ? $itemable : null;
    }
}
