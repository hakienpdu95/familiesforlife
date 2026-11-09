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

    public const REASON_HINTS = [
        'wrong_info' => ['Nội dung trên trang không khớp với thực tế', 'Thông tin nào chưa đúng, thực tế là gì…'],
        'suspected_fake' => ['Tem QR, bao bì hoặc nơi bán có dấu hiệu giả mạo', 'Mua ở đâu, khi nào, dấu hiệu bất thường bạn thấy…'],
        'expired' => ['Quá hạn sử dụng hoặc chứng nhận đã hết hiệu lực', 'Hạn ghi trên bao bì, nơi mua, ngày mua…'],
        'other' => ['Góp ý hoặc vấn đề khác', 'Điều bạn muốn ban quản lý biết…'],
    ];

    public const REF_PREFIX = 'PA-';

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

    public function ref(): string
    {
        return self::REF_PREFIX.str_pad((string) $this->id, 5, '0', STR_PAD_LEFT);
    }

    public static function idFromRef(string $value): ?int
    {
        return preg_match('/^\s*PA-?0*(\d+)\s*$/i', $value, $m) ? (int) $m[1] : null;
    }

    public function reasonLabel(): string
    {
        return self::REASONS[$this->reason] ?? $this->reason;
    }
}
