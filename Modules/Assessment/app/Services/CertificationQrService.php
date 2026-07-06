<?php

namespace Modules\Assessment\Services;

use Modules\Assessment\Models\WorkforceCertification;

class CertificationQrService
{
    public function generateAndStore(WorkforceCertification $cert): string
    {
        $verifyUrl = route('assessment.cert.verify', ['number' => $cert->certificate_number]);
        $cert->update(['qr_code_url' => $verifyUrl]);
        return $verifyUrl;
    }
}
