<?php

namespace Modules\ActivityLog\Models;

use Illuminate\Database\Eloquent\Model;

class ActivityLogContext extends Model
{
    public $timestamps = false;

    protected $fillable = [
        'log_id', 'key_name', 'value_type',
        'val_string', 'val_integer', 'val_decimal', 'val_boolean', 'val_datetime',
    ];

    protected $casts = [
        'val_boolean'  => 'boolean',
        'val_datetime' => 'datetime',
    ];

    public function typedValue(): mixed
    {
        return match ($this->value_type) {
            1       => $this->val_string,
            2       => $this->val_integer,
            3       => $this->val_decimal ? (float) $this->val_decimal : null,
            4       => $this->val_boolean,
            5       => $this->val_datetime,
            default => $this->val_string,
        };
    }
}
