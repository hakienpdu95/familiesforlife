<?php

namespace App\Shared\Traceability\Models;

use App\Shared\Traceability\Enums\QrScanSource;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/** Thống kê nội bộ — KHÔNG hiển thị trên trang truy xuất công khai. */
class QrScan extends Model
{
    public const DEDUPE_MINUTES = 30;

    public $timestamps = false;

    protected $table = 'qr_scans';

    protected $fillable = ['uuid', 'qr_identity_id', 'visitor_hash', 'client_hash', 'user_agent', 'scan_source', 'locale', 'scan_app', 'device_type', 'os', 'os_version', 'device_brand', 'is_repeat', 'scanned_at'];

    protected $casts = ['scanned_at' => 'datetime', 'scan_source' => QrScanSource::class, 'is_repeat' => 'boolean'];

    public function identity(): BelongsTo
    {
        return $this->belongsTo(QrIdentity::class, 'qr_identity_id');
    }
}
