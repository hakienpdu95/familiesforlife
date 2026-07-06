<?php
namespace Modules\Marketplace\Actions\Portal;

use Illuminate\Support\Facades\Hash;
use Lorisleiva\Actions\Concerns\AsAction;
use Modules\Marketplace\Data\Requests\RegisterApplicantData;
use Modules\Marketplace\Models\MktApplicant;

class RegisterApplicantAction
{
    use AsAction;

    public function handle(RegisterApplicantData $data): MktApplicant
    {
        return MktApplicant::create([
            'email'         => $data->email,
            'password_hash' => Hash::make($data->password),
            'display_name'  => $data->display_name,
            'location'      => $data->location,
            'headline'      => $data->headline,
        ]);
    }
}
