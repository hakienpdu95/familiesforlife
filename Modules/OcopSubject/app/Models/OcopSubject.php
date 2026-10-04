<?php

namespace Modules\OcopSubject\Models;

use App\Models\User;
use App\Traits\HasTenantMedia;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Str;
use Modules\Ocop\Models\OcopProduct;
use Modules\OcopSubject\Enums\OcopSubjectOrganizationType;
use Spatie\Activitylog\Models\Concerns\LogsActivity;
use Spatie\Activitylog\Support\LogOptions;
use Spatie\MediaLibrary\HasMedia;

class OcopSubject extends Model implements HasMedia
{
    use HasTenantMedia;
    use LogsActivity;
    use SoftDeletes;

    public const IMAGE_COLLECTION = 'ocop_subject_gallery';

    public const MAX_IMAGES = 10;

    public const DOC_BUSINESS_LICENSE = 'ocop_subject_business_licenses';

    public const DOC_OCOP_CERT = 'ocop_subject_ocop_certs';

    public const DOC_QUALITY_CERT = 'ocop_subject_quality_certs';

    public const DOC_FOOD_SAFETY_CERT = 'ocop_subject_food_safety_certs';

    public const DOCUMENT_COLLECTIONS = [
        self::DOC_BUSINESS_LICENSE,
        self::DOC_OCOP_CERT,
        self::DOC_QUALITY_CERT,
        self::DOC_FOOD_SAFETY_CERT,
    ];

    public const MAX_DOCUMENTS = 10;

    protected $table = 'ocop_subjects';

    protected $fillable = [
        'uuid', 'name', 'name_en', 'tax_code', 'organization_type',
        'legal_representative', 'position',
        'address', 'province_code', 'province_name', 'ward_code', 'ward_name',
        'gps_coordinates', 'factory_code', 'is_food_business',
        'ocop_star', 'ocop_cert_expiry',
        'hotline', 'email', 'website', 'is_active',
        'created_by', 'updated_by',
    ];

    protected $casts = [
        'organization_type' => OcopSubjectOrganizationType::class,
        'is_food_business' => 'boolean',
        'ocop_star' => 'integer',
        'ocop_cert_expiry' => 'date',
        'is_active' => 'boolean',
    ];

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->logFillable()
            ->logOnlyDirty()
            ->dontLogEmptyChanges();
    }

    protected static function booted(): void
    {
        static::creating(function (self $model): void {
            if (empty($model->uuid)) {
                $model->uuid = (string) Str::uuid();
            }
        });
    }

    public function getRouteKeyName(): string
    {
        return 'uuid';
    }

    public function products(): HasMany
    {
        return $this->hasMany(OcopProduct::class, 'ocop_subject_id');
    }

    public function createdBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function updatedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'updated_by');
    }

    public function fullAddress(): string
    {
        return collect([$this->address, $this->ward_name, $this->province_name])->filter()->implode(', ');
    }

    public function scopeActive(Builder $query): void
    {
        $query->where('is_active', true);
    }
}
