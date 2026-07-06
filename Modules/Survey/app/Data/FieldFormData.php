<?php

namespace Modules\Survey\Data;

use Modules\Survey\Enums\FieldType;
use Spatie\LaravelData\Data;

class FieldFormData extends Data
{
    public function __construct(
        public readonly ?string   $field_key = null,
        public readonly string    $label,
        public readonly FieldType $field_type,
        public readonly bool      $is_required     = false,
        public readonly ?int      $section_id       = null,
        public readonly ?int      $parent_field_id  = null,
        public readonly ?int      $rule_min         = null,
        public readonly ?int      $rule_max         = null,
        public readonly ?int      $rule_max_select  = null,
        public readonly ?string   $placeholder      = null,
    ) {}
}
