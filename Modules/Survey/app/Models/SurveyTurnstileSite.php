<?php

namespace Modules\Survey\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Facades\Crypt;

/**
 * Một "Turnstile site" = 1 widget Cloudflare Turnstile gắn với 1 domain bên
 * ngoài (vd thuchocvn.vn). Nhiều survey có thể trỏ chung vào 1 site — không
 * cần tạo key riêng cho từng survey khi số lượng survey lớn.
 */
class SurveyTurnstileSite extends Model
{
    protected $table = 'survey_turnstile_sites';

    protected $fillable = [
        'name',
        'site_key',
        'secret_key_encrypted',
        'is_active',
    ];

    // Không serialize secret ra ngoài API response
    protected $hidden = ['secret_key_encrypted'];

    protected $casts = [
        'is_active' => 'boolean',
    ];

    // ── Relations ─────────────────────────────────────────────────────

    public function surveys(): HasMany
    {
        return $this->hasMany(Survey::class, 'turnstile_site_id');
    }

    // ── Scopes ────────────────────────────────────────────────────────

    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    // ── Helpers ───────────────────────────────────────────────────────

    public function secretKey(): ?string
    {
        if (blank($this->secret_key_encrypted)) {
            return null;
        }

        try {
            return Crypt::decryptString($this->secret_key_encrypted);
        } catch (\Throwable) {
            return null;
        }
    }
}
