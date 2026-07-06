<?php
namespace Modules\Customer\Models;

use App\Foundation\Models\TenantAwareModel;
use App\Models\User;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Modules\Customer\Enums\CompanySize;
use Modules\Customer\Enums\CustomerLifecycleStage;
use Modules\Customer\Enums\CustomerType;
use Modules\LeadSource\Models\LeadSource;
use Spatie\Activitylog\Support\LogOptions;

class Customer extends TenantAwareModel
{
    protected $table = 'customers';

    protected $fillable = [
        'organization_id', 'uuid', 'customer_type',
        'display_name', 'primary_email', 'secondary_email', 'primary_phone', 'secondary_phone',
        'province_code', 'province_name', 'ward_code', 'ward_name', 'address_line',
        'full_address', 'website', 'description', 'notes', 'avatar_url',
        'lifecycle_stage', 'source_id', 'assigned_to',
        'last_activity_at', 'activity_count',
        'first_name', 'last_name', 'gender', 'date_of_birth',
        'company_name', 'tax_code', 'industry', 'company_size',
        'representative_name', 'representative_title',
        'dedup_hash', 'converted_from_lead_id', 'created_by', 'updated_by',
    ];

    protected $casts = [
        'customer_type'      => CustomerType::class,
        'lifecycle_stage'    => CustomerLifecycleStage::class,
        'company_size'       => CompanySize::class,
        'date_of_birth'      => 'date',
        'last_activity_at'   => 'datetime',
    ];

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->useLogName('customer')
            ->logOnly(['display_name', 'lifecycle_stage', 'assigned_to', 'customer_type'])
            ->logOnlyDirty()
            ->dontLogEmptyChanges();
    }

    public function getActivityLabel(): string
    {
        return $this->display_name;
    }

    public function source(): BelongsTo
    {
        return $this->belongsTo(LeadSource::class, 'source_id');
    }

    public function assignee(): BelongsTo
    {
        return $this->belongsTo(User::class, 'assigned_to');
    }

    public function createdBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function tags(): BelongsToMany
    {
        return $this->belongsToMany(CustomerTag::class, 'customer_tag_map', 'customer_id', 'tag_id');
    }

    public function meta(): HasMany
    {
        return $this->hasMany(CustomerMeta::class, 'customer_id');
    }

    public function fieldDefinitions(): HasMany
    {
        return $this->hasMany(CustomerFieldDefinition::class, 'organization_id', 'organization_id');
    }

    public function leads(): HasMany
    {
        return $this->hasMany(\Modules\Lead\Models\Lead::class, 'customer_id');
    }

    public function convertedFromLead(): BelongsTo
    {
        return $this->belongsTo(\Modules\Lead\Models\Lead::class, 'converted_from_lead_id');
    }

    public function activities(): HasMany
    {
        return $this->hasMany(CustomerActivity::class, 'customer_id');
    }

    // Đặt tên khác 'notes' vì đã có cột 'notes' (ghi chú nội bộ dạng text) trên
    // chính bảng customers — Eloquent ưu tiên resolve thuộc tính trước quan hệ,
    // nên $customer->notes luôn trả về cột text chứ không bao giờ tới được đây.
    public function customerNotes(): HasMany
    {
        return $this->hasMany(CustomerNote::class, 'customer_id');
    }

    protected static function booted(): void
    {
        static::creating(function (self $model) {
            if (empty($model->uuid)) {
                $model->uuid = (string) \Illuminate\Support\Str::uuid();
            }
        });
    }
}
