<?php

namespace Modules\PensionCalculator\Models;

use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * spec §6.6/§8.1 — tra theo cặp (settlement_year, contribution_year). Nhập lại nguyên văn bảng
 * hệ số trượt giá BHXH Việt Nam công bố hàng năm — module KHÔNG tự tính CPI.
 */
class PensionPriceIndexCoefficient extends Model
{
    protected $fillable = [
        'settlement_year',
        'contribution_year',
        'coefficient',
        'source_document',
        'created_by',
    ];

    protected $casts = [
        'settlement_year' => 'integer',
        'contribution_year' => 'integer',
        'coefficient' => 'decimal:2',
    ];

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }
}
