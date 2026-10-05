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

    public const DOCUMENT_LABELS = [
        self::DOC_BUSINESS_LICENSE => 'Giấy chứng nhận đăng ký kinh doanh',
        self::DOC_OCOP_CERT => 'Quyết định phê duyệt hạng sao OCOP',
        self::DOC_QUALITY_CERT => 'Chứng nhận chất lượng',
        self::DOC_FOOD_SAFETY_CERT => 'Giấy chứng nhận cơ sở đủ điều kiện ATTP',
    ];

    public const MAX_DOCUMENTS = 10;

    protected $table = 'ocop_subjects';

    protected $fillable = [
        'uuid', 'name', 'name_en', 'slug', 'tax_code', 'organization_type',
        'legal_representative', 'position',
        'address', 'province_code', 'province_name', 'ward_code', 'ward_name',
        'gps_coordinates', 'factory_code', 'is_food_business',
        'ocop_star', 'ocop_cert_expiry',
        'hotline', 'email', 'website', 'story', 'is_active',
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

            if (empty($model->slug)) {
                $model->slug = self::uniqueSlug($model->name);
            }
        });
    }

    public static function uniqueSlug(string $name): string
    {
        $base = Str::slug($name) ?: 'chu-the';
        $slug = $base;
        $i = 2;

        while (static::withTrashed()->where('slug', $slug)->exists()) {
            $slug = "{$base}-{$i}";
            $i++;
        }

        return $slug;
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

    public function storyMediaUuids(): array
    {
        preg_match_all('/data-media-uuid="([^"]+)"/', (string) $this->story, $matches);

        return array_values(array_unique($matches[1]));
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
