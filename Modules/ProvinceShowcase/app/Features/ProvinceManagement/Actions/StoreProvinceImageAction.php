<?php

namespace Modules\ProvinceShowcase\Features\ProvinceManagement\Actions;

use Illuminate\Http\UploadedFile;
use Illuminate\Support\Str;
use Lorisleiva\Actions\Concerns\AsAction;

class StoreProvinceImageAction
{
    use AsAction;

    public function handle(UploadedFile $file, string $directory): string
    {
        return $file->storeAs("provinces/{$directory}", Str::uuid().'.'.$file->extension(), 'public');
    }
}
