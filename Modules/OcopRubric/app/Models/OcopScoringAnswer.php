<?php

namespace Modules\OcopRubric\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * 1 câu trả lời cho 1 tiêu chí trong 1 session. KHÔNG extend TenantAwareModel —
 * bảng `ocop_scoring_answers` không có cột organization_id (xem migration §7);
 * tenant-scoping thực hiện gián tiếp qua session_id → ocop_scoring_sessions.
 */
class OcopScoringAnswer extends Model
{
    protected $table = 'ocop_scoring_answers';

    protected $fillable = [
        'session_id', 'criterion_id', 'option_id', 'points_awarded',
        'needs_review', 'evidence_note', 'answered_at',
    ];

    protected $casts = [
        'points_awarded' => 'decimal:2',
        'needs_review'   => 'boolean',
        'answered_at'    => 'datetime',
    ];

    public function session(): BelongsTo
    {
        return $this->belongsTo(OcopScoringSession::class, 'session_id');
    }

    public function criterion(): BelongsTo
    {
        return $this->belongsTo(OcopRubricCriterion::class, 'criterion_id');
    }

    public function option(): BelongsTo
    {
        return $this->belongsTo(OcopRubricOption::class, 'option_id');
    }
}
