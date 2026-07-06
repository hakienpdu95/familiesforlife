<?php

namespace App\View\Components;

use App\Models\Province;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\View\Component;

class AddressPicker extends Component
{
    public Collection $provinces;

    public function __construct(
        public ?string $provinceValue = null,
        public ?string $wardValue     = null,
        public bool    $required      = false,
        public string  $instanceId    = 'addr',
        public string  $nameProvince  = 'province_code',
        public string  $nameWard      = 'ward_code',
    ) {
        $this->provinces = Province::orderBy('name')
            ->get(['province_code', 'name', 'place_type']);
    }

    public function render(): \Illuminate\Contracts\View\View
    {
        return view('components.address-picker');
    }
}
