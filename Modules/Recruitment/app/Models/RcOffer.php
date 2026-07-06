<?php

namespace Modules\Recruitment\Models;

use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Str;
use Modules\Recruitment\Enums\OfferStatus;

class RcOffer extends Model
{
    protected $table = 'rc_offers';

    protected $fillable = [
        'uuid',
        'application_id',
        'salary_offered',
        'currency',
        'start_date',
        'probation_days',
        'benefits_note',
        'expire_at',
        'status',
        'approved_by',
        'approved_at',
        'sent_at',
        'responded_at',
        'rejection_reason',
        'created_by',
    ];

    protected $casts = [
        'status'       => OfferStatus::class,
        'salary_offered' => 'decimal:2',
        'start_date'   => 'date',
        'expire_at'    => 'date',
        'approved_at'  => 'datetime',
        'sent_at'      => 'datetime',
        'responded_at' => 'datetime',
    ];

    protected static function booted(): void
    {
        static::creating(function (self $model): void {
            if (empty($model->uuid)) {
                $model->uuid = (string) Str::uuid();
            }
            if (empty($model->created_by)) {
                $model->created_by = auth()->id();
            }
        });
    }

    public function application(): BelongsTo
    {
        return $this->belongsTo(RcApplication::class, 'application_id');
    }

    public function approvedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'approved_by');
    }

    public function createdBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }
}
