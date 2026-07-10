<?php

namespace Modules\Aicem\Models;

use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Modules\Aicem\Enums\SuggestionStatus;

/**
 * Không extends TenantAwareModel — luôn truy cập qua AicemGenerationRun cha, không cần global
 * scope riêng, nhưng vẫn giữ organization_id để truy vết trực tiếp khi audit (mục 7).
 */
class AicemSuggestion extends Model
{
    protected $table = 'aicem_suggestions';

    protected $fillable = [
        'generation_run_id',
        'organization_id',
        'field',
        'block_id',
        'original_text',
        'suggested_text',
        'reason',
        'status',
        'decided_by',
        'decided_at',
    ];

    protected function casts(): array
    {
        return [
            'status'     => SuggestionStatus::class,
            'block_id'   => 'integer',
            'decided_at' => 'datetime',
        ];
    }

    public function generationRun(): BelongsTo
    {
        return $this->belongsTo(AicemGenerationRun::class, 'generation_run_id');
    }

    public function decidedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'decided_by');
    }
}
