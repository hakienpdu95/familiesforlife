<?php

namespace Modules\PensionCalculator\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Modules\PensionCalculator\Enums\SupportGroupKey;

class PensionSupportTier extends Model
{
    protected $fillable = [
        'period_id',
        'group_key',
        'support_percent',
    ];

    protected $casts = [
        'group_key' => SupportGroupKey::class,
        'support_percent' => 'decimal:2',
    ];

    public function period(): BelongsTo
    {
        return $this->belongsTo(PensionParameterPeriod::class, 'period_id');
    }
}
