<?php

namespace Modules\OcopRubric\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Tự-đánh dấu rủi ro loại hồ sơ trong 1 session (advisory, không tự động chặn
 * hoàn thành). KHÔNG extend TenantAwareModel — không có cột organization_id.
 */
class OcopScoringDisqualifierFlag extends Model
{
    protected $table = 'ocop_scoring_disqualifier_flags';

    protected $fillable = ['session_id', 'disqualifier_id', 'is_flagged'];

    protected $casts = [
        'is_flagged' => 'boolean',
    ];

    public function session(): BelongsTo
    {
        return $this->belongsTo(OcopScoringSession::class, 'session_id');
    }

    public function disqualifier(): BelongsTo
    {
        return $this->belongsTo(OcopRubricDisqualifier::class, 'disqualifier_id');
    }
}
