<?php

namespace App\Shared\Traceability\Models;

use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class QrReport extends Model
{
    public const REASONS = [
        'wrong_info' => 'Thông tin không chính xác',
        'suspected_fake' => 'Nghi ngờ hàng giả / tem giả',
        'expired' => 'Sản phẩm hết hạn / chứng nhận hết hiệu lực',
        'other' => 'Khác',
    ];

    protected $table = 'qr_reports';

    protected $fillable = ['qr_identity_id', 'reason', 'content', 'contact', 'visitor_hash', 'status', 'resolved_by', 'resolved_at'];

    protected $casts = ['resolved_at' => 'datetime'];

    public function identity(): BelongsTo
    {
        return $this->belongsTo(QrIdentity::class, 'qr_identity_id');
    }

    public function resolvedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'resolved_by');
    }

    public static function visitorHash(?string $ip): string
    {
        return hash('sha256', (string) $ip.'|'.config('app.key'));
    }

    public function reasonLabel(): string
    {
        return self::REASONS[$this->reason] ?? $this->reason;
    }
}
