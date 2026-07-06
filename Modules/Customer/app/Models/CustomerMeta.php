<?php
namespace Modules\Customer\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Modules\Customer\Enums\MetaValueType;

class CustomerMeta extends Model
{
    protected $table = 'customer_meta';

    protected $fillable = [
        'customer_id', 'definition_id',
        'val_string', 'val_integer', 'val_decimal', 'val_boolean', 'val_date',
    ];

    protected $casts = [
        'val_boolean' => 'boolean',
        'val_date'    => 'date',
    ];

    public function definition(): BelongsTo
    {
        return $this->belongsTo(CustomerFieldDefinition::class, 'definition_id');
    }

    public function customer(): BelongsTo
    {
        return $this->belongsTo(Customer::class);
    }

    public function getValue(): mixed
    {
        $type = $this->definition?->value_type;
        return match($type?->value ?? MetaValueType::String->value) {
            MetaValueType::Integer->value => $this->val_integer,
            MetaValueType::Decimal->value => $this->val_decimal,
            MetaValueType::Boolean->value => $this->val_boolean,
            MetaValueType::Date->value    => $this->val_date,
            default                       => $this->val_string,
        };
    }
}
