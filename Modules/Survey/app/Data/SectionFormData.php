<?php

namespace Modules\Survey\Data;

use Spatie\LaravelData\Data;

class SectionFormData extends Data
{
    public function __construct(
        public readonly string  $title,
        public readonly ?string $icon = null,
    ) {}
}
