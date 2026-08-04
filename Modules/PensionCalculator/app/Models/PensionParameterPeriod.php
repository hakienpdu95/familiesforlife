<?php

namespace Modules\PensionCalculator\Models;

use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * spec/bhxh/PensionCalculator_Technical_Specification.md §0/§4/§8 — tham số BHXH tự nguyện
 * áp dụng thống nhất toàn quốc, KHÔNG organization_id, KHÔNG extends TenantAwareModel — cùng
 * mô hình Page/MenuItem/Banner. Bất biến sau khi tạo (§9.1): không route edit/destroy.
 */
class PensionParameterPeriod extends Model
{
    protected $fillable = [
        'effective_from',
        'rural_poverty_line',
        'reference_level',
        'contribution_rate_percent',
        'ceiling_multiplier',
        'source_document',
        'notes',
        'created_by',
    ];

    protected $casts = [
        'effective_from' => 'date',
        'rural_poverty_line' => 'decimal:2',
        'reference_level' => 'decimal:2',
        'contribution_rate_percent' => 'decimal:2',
        'ceiling_multiplier' => 'integer',
    ];

    public function supportTiers(): HasMany
    {
        return $this->hasMany(PensionSupportTier::class, 'period_id');
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    /** Trần đóng hiện hành = ceiling_multiplier × reference_level (§2.2/§6.1 — KHÔNG hard-code số tuyệt đối). */
    public function contributionCeiling(): string
    {
        return bcmul((string) $this->reference_level, (string) $this->ceiling_multiplier, 2);
    }
}
