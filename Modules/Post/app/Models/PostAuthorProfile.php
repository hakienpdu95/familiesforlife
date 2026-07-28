<?php

namespace Modules\Post\Models;

use App\Models\User;
use App\Traits\HasTenantMedia;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Str;
use Spatie\MediaLibrary\HasMedia;

/**
 * spec/Author_Contributor_Hub_Technical_Specification.md §3/§4 — hồ sơ tác giả công khai,
 * 1-1 với User. Avatar qua Media Library (collection "avatar", §2.6) — KHÔNG có cột avatar
 * riêng, fallback Dicebear ở tầng view nếu chưa upload (giữ nguyên hành vi profile.blade.php).
 */
class PostAuthorProfile extends Model implements HasMedia
{
    use HasTenantMedia;

    protected $table = 'post_author_profiles';

    protected $fillable = [
        'user_id', 'slug', 'pen_name', 'bio', 'social_links', 'is_public', 'job_title', 'credentials',
    ];

    protected $casts = [
        'social_links' => 'array',
        'is_public'    => 'boolean',
    ];

    public function getRouteKeyName(): string
    {
        return 'slug';
    }

    // ── Relationships ────────────────────────────────────────────────

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    // ── Display helpers ──────────────────────────────────────────────

    /** Tên hiển thị công khai — ưu tiên bút danh, fallback tên tài khoản thật. */
    public function displayName(): string
    {
        return $this->pen_name ?: (string) $this->user?->name;
    }

    /** URL avatar — ảnh thật nếu đã upload, fallback Dicebear (§2.6) nếu chưa. */
    public function avatarUrl(): string
    {
        return $this->getFirstMediaUrl('avatar', 'thumb')
            ?: 'https://api.dicebear.com/9.x/initials/svg?seed='
                . urlencode($this->displayName())
                . '&backgroundColor=6366f1&fontFamily=Arial&fontSize=40&fontWeight=700';
    }

    public static function slugFor(User $user, ?string $penName = null): string
    {
        return Str::slug($penName ?: $user->name) . '-' . $user->id;
    }
}
