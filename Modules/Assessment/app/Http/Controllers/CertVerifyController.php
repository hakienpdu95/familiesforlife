<?php

namespace Modules\Assessment\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\View\View;
use Modules\Assessment\Models\WorkforceCertification;

class CertVerifyController extends Controller
{
    public function show(string $number): View
    {
        $cert = WorkforceCertification::where('certificate_number', $number)
            ->with([
                'definition:id,name,level_code,cert_code',
                'profile.employee:id,full_name',
            ])
            ->firstOrFail();

        return view('assessment::certifications.verify', compact('cert'));
    }
}
