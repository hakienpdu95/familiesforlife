<?php

namespace Modules\Survey\Data;

use Spatie\LaravelData\Data;

class OptionFormData extends Data
{
    public function __construct(
        public readonly string $option_value,
        public readonly string $label,
        public readonly bool   $is_other = false,
    ) {}
}
