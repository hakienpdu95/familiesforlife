<?php

namespace App\Shared\Traceability\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/** Thống kê nội bộ — KHÔNG hiển thị trên trang truy xuất công khai. */
class QrScan extends Model
{
    public const DEDUPE_MINUTES = 30;

    public $timestamps = false;

    protected $table = 'qr_scans';

    protected $fillable = ['qr_identity_id', 'visitor_hash', 'user_agent', 'scanned_at'];

    protected $casts = ['scanned_at' => 'datetime'];

    public function identity(): BelongsTo
    {
        return $this->belongsTo(QrIdentity::class, 'qr_identity_id');
    }
}
