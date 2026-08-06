<?php

namespace Modules\N8n\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Str;
use Spatie\Activitylog\Models\Concerns\LogsActivity;
use Spatie\Activitylog\Support\LogOptions;

/**
 * spec/N8n_Integration_Technical_Specification.md §2.1 — đơn vị cấu hình trung tâm, thuộc HỆ
 * THỐNG, KHÔNG thuộc tổ chức nào (§0). CỐ Ý KHÔNG `extends TenantAwareModel` — khác mọi model
 * nghiệp vụ khác trong Modules — đây là hạ tầng tích hợp platform-wide, gate bằng Platform
 * Roles (§6), không bằng tenant scope. Cùng mẫu với `Plan`
 * (vendor/laravelcm/laravel-subscriptions, bảng `plans`).
 */
class N8nConnection extends Model
{
    use HasFactory;
    use LogsActivity;
    use SoftDeletes;

    protected $table = 'n8n_connections';

    protected $fillable = [
        'uuid',
        'name',
        'purpose_note',
        'inbound_enabled',
        'outbound_enabled',
        'inbound_token',
        'inbound_secret',
        'outbound_webhook_url',
        'outbound_secret',
        'allowed_ip_cidrs',
        'rate_limit_per_minute',
        'last_inbound_at',
        'last_outbound_at',
        'created_by',
        'updated_by',
    ];

    /**
     * §2.1 — `inbound_secret`/`outbound_webhook_url`/`outbound_secret` là credential, không lưu
     * plaintext (cùng tiền lệ `Organization.ai_provider_config` — 'encrypted:array'). `inbound_token`
     * CỐ Ý không encrypted — đóng vai trò định tuyến (§2.2), cần `WHERE inbound_token = ?` trực
     * tiếp mà cast `encrypted` không cho phép (ciphertext khác nhau mỗi lần dù cùng giá trị gốc).
     */
    protected $casts = [
        'inbound_enabled' => 'boolean',
        'outbound_enabled' => 'boolean',
        'inbound_secret' => 'encrypted',
        'outbound_webhook_url' => 'encrypted',
        'outbound_secret' => 'encrypted',
        'allowed_ip_cidrs' => 'array',
        'rate_limit_per_minute' => 'integer',
        'last_inbound_at' => 'datetime',
        'last_outbound_at' => 'datetime',
    ];

    protected static function booted(): void
    {
        static::creating(function (self $model): void {
            if (empty($model->uuid)) {
                $model->uuid = (string) Str::uuid();
            }
        });
    }

    public function getRouteKeyName(): string
    {
        return 'uuid';
    }

    /**
     * §7.2 — whitelist tường minh (`logOnly`, KHÔNG `logFillable`): 4 field bí mật
     * (`inbound_token`/`inbound_secret`/`outbound_webhook_url`/`outbound_secret`) không bao giờ
     * xuất hiện trong activity log, kể cả dạng ciphertext đã encrypted.
     */
    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->logOnly(['name', 'purpose_note', 'inbound_enabled', 'outbound_enabled', 'allowed_ip_cidrs', 'rate_limit_per_minute'])
            ->logOnlyDirty()
            ->dontLogEmptyChanges();
    }

    public function inboundLogs(): HasMany
    {
        return $this->hasMany(N8nInboundLog::class, 'connection_id');
    }

    public function outboundLogs(): HasMany
    {
        return $this->hasMany(N8nOutboundLog::class, 'connection_id');
    }

    /**
     * §4.1 — badge "Outbound: chưa ký" khi outbound_secret rỗng nhưng outbound_enabled=true.
     */
    public function sendsUnsignedOutbound(): bool
    {
        return $this->outbound_enabled && empty($this->outbound_secret);
    }

    /**
     * §3.2 — placeholder che, giữ 4 ký tự cuối để phân biệt các secret khi xoay nhiều lần.
     * KHÔNG bao giờ trả giá trị đầy đủ qua bất kỳ endpoint GET nào sau lần tạo/xoay đầu tiên.
     */
    public static function maskSecret(?string $value): ?string
    {
        if (empty($value)) {
            return null;
        }

        return '••••••••'.substr($value, -4);
    }

    public function maskedInboundToken(): ?string
    {
        return self::maskSecret($this->inbound_token);
    }

    public function maskedInboundSecret(): ?string
    {
        return self::maskSecret($this->inbound_secret);
    }

    public function maskedOutboundSecret(): ?string
    {
        return self::maskSecret($this->outbound_secret);
    }
}
