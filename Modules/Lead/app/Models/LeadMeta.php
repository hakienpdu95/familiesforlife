<?php

namespace Modules\Lead\Models;

use Illuminate\Database\Eloquent\Model;
use Modules\Lead\Enums\MetaValueType;

class LeadMeta extends Model
{
    protected $table = 'lead_meta';

    protected $fillable = [
        'lead_id', 'key_name', 'value_type',
        'val_string', 'val_integer', 'val_decimal',
        'val_boolean', 'val_datetime',
    ];

    protected $casts = [
        'value_type'   => MetaValueType::class,
        'val_boolean'  => 'boolean',
        'val_datetime' => 'datetime',
    ];

    public function getValue(): mixed
    {
        return match($this->value_type) {
            MetaValueType::Integer  => $this->val_integer,
            MetaValueType::Decimal  => $this->val_decimal,
            MetaValueType::Boolean  => $this->val_boolean,
            MetaValueType::Datetime => $this->val_datetime,
            default                 => $this->val_string,
        };
    }
}
