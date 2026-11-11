<?php

namespace App\Actions;

use App\Models\SystemSetting;
use Lorisleiva\Actions\Concerns\AsAction;

class UpdateSystemSettingsAction
{
    use AsAction;

    public function handle(array $data): SystemSetting
    {
        $setting = SystemSetting::query()->first() ?? new SystemSetting;
        $setting->fill($data)->save();

        return $setting;
    }
}
