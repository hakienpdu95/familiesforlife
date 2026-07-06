<?php

namespace Modules\Marketplace\Models;

use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Str;
use Modules\Marketplace\Enums\ApplicationImportStatus;
use Modules\Marketplace\Enums\ApplicationStatus;

class MktApplication extends Model
{
    protected $table = 'mkt_applications';
    const CREATED_AT = 'applied_at';

    protected $fillable = [
        'uuid', 'listing_id', 'applicant_id', 'status', 'cover_letter',
        'expected_salary', 'available_from', 'portfolio_url',
        'import_status', 'imported_rc_candidate_id', 'imported_rc_application_id',
        'imported_at', 'imported_by', 'viewed_at',
    ];

    protected $casts = [
        'status'         => ApplicationStatus::class,
        'import_status'  => ApplicationImportStatus::class,
        'available_from' => 'date',
        'imported_at'    => 'datetime',
        'viewed_at'      => 'datetime',
        'applied_at'     => 'datetime',
    ];

    protected static function booted(): void
    {
        static::creating(function (self $model): void {
            if (empty($model->uuid)) {
                $model->uuid = (string) Str::uuid();
            }
        });
    }

    // ── Relationships ────────────────────────────────────────────────

    public function listing(): BelongsTo
    {
        return $this->belongsTo(MktListing::class, 'listing_id');
    }

    public function applicant(): BelongsTo
    {
        return $this->belongsTo(MktApplicant::class, 'applicant_id');
    }

    public function importedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'imported_by');
    }

    public function reviews(): HasMany
    {
        return $this->hasMany(MktReview::class, 'application_id');
    }

    // ── Helpers ──────────────────────────────────────────────────────

    public function canWithdraw(): bool
    {
        return in_array($this->status, [ApplicationStatus::Submitted, ApplicationStatus::Viewed]);
    }

    public function canReview(): bool
    {
        return $this->status === \Modules\Marketplace\Enums\ApplicationStatus::Hired;
    }

    public function canImport(): bool
    {
        return $this->import_status === ApplicationImportStatus::NotImported
            && $this->listing?->jp_job_post_id !== null;
    }
}
