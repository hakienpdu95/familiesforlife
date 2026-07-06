<?php

namespace Modules\Lead\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class LeadContact extends Model
{
    use SoftDeletes;

    protected $table = 'lead_contacts';

    protected $fillable = [
        'organization_id', 'full_name', 'email', 'phone', 'phone_alt',
        'company', 'job_title', 'website',
        'address', 'ward_code', 'ward_name',
        'district_code', 'district_name',
        'province_code', 'province_name', 'country_code',
        'dedup_hash', 'lead_count', 'created_by',
    ];

    public function leads()
    {
        return $this->hasMany(Lead::class, 'contact_id');
    }

    public function locationLabel(): string
    {
        return implode(', ', array_filter([
            $this->ward_name,
            $this->district_name,
            $this->province_name,
        ]));
    }
}
