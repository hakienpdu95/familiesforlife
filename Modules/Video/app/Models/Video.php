<?php

namespace Modules\Video\Models;

use App\Models\User;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Str;
use Spatie\Activitylog\Models\Concerns\LogsActivity;
use Spatie\Activitylog\Support\LogOptions;

/**
 * spec/Video_Management_Technical_Specification.md §3/§4 — video là tài sản nền tảng
 * (platform), không organization_id, không phân cấp — cùng nguyên tắc Banner/Event/MenuItem.
 */
class Video extends Model
{
    use SoftDeletes;
    use LogsActivity;

    protected $fillable = [
        'uuid', 'name', 'description', 'video_url', 'embed_code', 'youtube_video_id',
        'sort_order', 'is_active', 'created_by', 'updated_by',
    ];

    protected $casts = [
        'is_active'  => 'boolean',
        'sort_order' => 'integer',
    ];

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->logFillable()
            ->logOnlyDirty()
            ->dontLogEmptyChanges();
    }

    protected static function booted(): void
    {
        static::creating(function (self $video): void {
            $video->uuid ??= (string) Str::uuid();
        });
    }

    public function getRouteKeyName(): string
    {
        return 'uuid';
    }

    // ── Relationships ────────────────────────────────────────────────

    public function createdBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function updatedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'updated_by');
    }

    // ── Scopes ───────────────────────────────────────────────────────

    public function scopeActive(Builder $query): void
    {
        $query->where('is_active', true);
    }

    // ── Accessors (§4.1 — mọi output công khai LUÔN dựng từ youtube_video_id đã validate,
    // KHÔNG BAO GIỜ render trực tiếp embed_code/video_url thô — chống stored-XSS/phishing) ──

    /**
     * Ảnh đại diện AN TOÀN (480×360) — LUÔN suy ra từ youtube_video_id đã validate, không có
     * cột lưu file, không phụ thuộc Media Library. `hqdefault.jpg` tồn tại cho MỌI video
     * YouTube (khác `maxresdefault.jpg`/`thumbnail_hd_url` không phải video nào cũng có bản độ
     * phân giải cao) — dùng làm giá trị FALLBACK khi `thumbnail_hd_url` không tải được ảnh HD
     * thật (view tự kiểm tra bằng JS, xem `thumbnail_hd_url`).
     */
    public function getThumbnailUrlAttribute(): string
    {
        return "https://i.ytimg.com/vi/{$this->youtube_video_id}/hqdefault.jpg";
    }

    /**
     * Ảnh đại diện Full HD (1280×720) — thử trước để có chất lượng cao nhất có thể. LƯU Ý:
     * video không có bản HD KHÔNG trả lỗi 404 thật — YouTube trả về 1 ảnh xám placeholder cỡ
     * đúng 120×90 kèm HTTP 200, nên phía view KHÔNG thể chỉ dùng `onerror` để phát hiện thiếu
     * ảnh HD (ảnh "lỗi" vẫn tải thành công). View phải tự kiểm tra `naturalWidth` sau khi ảnh
     * tải xong (`onload`) và đổi `src` sang `thumbnail_url` (hqdefault, luôn tồn tại) nếu phát
     * hiện đúng kích thước placeholder đó — xem `Modules/Video/resources/assets/js/video.js`
     * (`window.videoThumbCheck`) dùng chung cho cả trang quản trị lẫn trang công khai.
     */
    public function getThumbnailHdUrlAttribute(): string
    {
        return "https://i.ytimg.com/vi/{$this->youtube_video_id}/maxresdefault.jpg";
    }

    /**
     * URL nhúng an toàn — LUÔN dựng từ youtube_video_id đã validate, KHÔNG liên quan gì tới
     * chuỗi embed_code người dùng đã nhập (§0/§5.2 — lý do chống XSS).
     */
    public function getEmbedUrlAttribute(): string
    {
        $domain = config('video.embed_domain', 'www.youtube-nocookie.com');

        return "https://{$domain}/embed/{$this->youtube_video_id}";
    }

    /**
     * Link "Xem trên YouTube" — CHỈ dùng video_url gốc nếu host của nó khớp whitelist
     * (`config('video.allowed_hosts')` — NGUỒN DUY NHẤT, dùng chung với
     * ResolveYoutubeVideoIdAction::isWhitelistedHost()). Đã validate khi lưu (§5.2), nhưng
     * kiểm tra lại ở đây làm lớp phòng thủ thứ 2 — phòng trường hợp dữ liệu vào DB không qua
     * Action (vd sửa tay/migration/seeder sai). Không khớp hoặc trống → LUÔN fallback về URL
     * dựng từ youtube_video_id đã validate, KHÔNG BAO GIỜ trả thẳng 1 chuỗi không rõ nguồn gốc
     * — chống vector phishing nếu tài khoản video.manage bị chiếm (§0). So khớp CHÍNH XÁC bằng
     * parse_url()+in_array (không phải regex tìm chuỗi con) — tránh bị qua mặt bởi URL kiểu
     * `https://evil.com/?next=https://youtube.com/...` (host thật là `evil.com`).
     */
    public function getWatchUrlAttribute(): string
    {
        if ($this->video_url) {
            $host = parse_url($this->video_url, PHP_URL_HOST);

            if ($host !== null && in_array($host, config('video.allowed_hosts', []), true)) {
                return $this->video_url;
            }
        }

        return "https://www.youtube.com/watch?v={$this->youtube_video_id}";
    }
}
