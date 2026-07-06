<?php
namespace Modules\Customer\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Modules\Customer\Enums\MetaValueType;

class CustomerFieldDefinition extends Model
{
    protected $table = 'customer_field_definitions';

    protected $fillable = [
        'organization_id', 'field_key', 'label', 'value_type',
        'is_required', 'default_value', 'placeholder', 'sort_order',
        'applies_to', 'is_active',
    ];

    protected $casts = [
        'value_type'  => MetaValueType::class,
        'is_required' => 'boolean',
        'is_active'   => 'boolean',
    ];

    public function meta(): HasMany
    {
        return $this->hasMany(CustomerMeta::class, 'definition_id');
    }

    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    public function scopeForType($query, int $type)
    {
        // applies_to: 0=Both, 1=Individual, 2=Business
        return $query->where(fn ($q) => $q->where('applies_to', 0)->orWhere('applies_to', $type));
    }
}
